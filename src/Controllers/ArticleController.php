<?php

namespace Themes\Saniar\src\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Like; // تغییر: استفاده از Like به جای PostLike
use App\Models\Product;
use App\Models\Tag;
use App\Models\Category;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::query()
            ->with(['admin', 'category', 'tags'])
            ->withCount('likes') // این هنوز کار می‌کند (رابطه در مدل Post)
            ->published();

        // سانیار فاز K: جستجوی عنوان (q) — در WeblakShop روت blog/search به متد
        // ناموجود پوینت می‌کرد و جستجوی وبلاگ عملاً از کار افتاده بود.
        if ($request->filled('q')) {
            $posts->where('title', 'like', '%' . str_replace(' ', '%', trim($request->q)) . '%');
        }

        // فیلتر بر اساس دسته بندی
        // سانیار فاز K: پست‌ها FK تکی category_id دارند؛ رابطهٔ categories در مدل
        // میزبان تعریف نشده و with/whereHas آن در WeblakShop → RelationNotFound → ۵۰۰ همیشگی.
        if ($request->filled('cat')) {
            $category = Category::where('slug', $request->cat)->where('type', 'postcat')->first();
            if ($category) {
                $posts->where('category_id', $category->id);
            }
        }

        // فیلتر بر اساس تگ
        if ($request->filled('tag')) {
            $tag = Tag::where('slug', $request->tag)->first();
            if ($tag) {
                $posts->whereHas('tags', function ($q) use ($tag) {
                    $q->where('tags.id', $tag->id);
                });
            }
        }

        // فیلتر بر اساس نویسنده
        $theAuthor = null;
        if ($request->filled('profile')) {
            $author = Admin::where('username', $request->profile)->first();
            if ($author) {
                $posts->where('admin_id', $author->id);
            }
            $theAuthor = $author;
        }

        // مرتب سازی بر اساس sort
        switch ($request->get('sort', 'latest')) {
            case 'latest':
                $posts->latest();
                break;
            case 'most_viewed':
                $posts->orderBy('view', 'desc');
                break;
            case 'most_popular':
                $posts->orderBy('likes_count', 'desc');
                break;
            case 'most_commented':
                $posts->withCount(['acceptedComments' => function($query) {
                    $query->where('status', 'accepted');
                }])->orderBy('accepted_comments_count', 'desc');
                break;
            case 'editor_pick':
                $posts->where('is_editor_pick', true)->latest();
                break;
            default:
                $posts->latest();
        }

        $posts = $posts->paginate(12);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('front::articles.partials.articles-list', compact('posts'))->render(),
                'pagination' => view('front::articles.partials.pagination', compact('posts'))->render(),
                'total' => $posts->total()
            ]);
        }

        return view('front::articles.index', compact('posts', 'theAuthor'));
    }

    public function show($slug)
    {
        $post = Post::where('slug', $slug)
            ->with(['admin', 'category', 'tags'])
            ->withCount(['likes', 'comments'])
            ->published()
            ->firstOrFail();

        // افزایش بازدید
        $post->increment('view');

        // مقالات مرتبط (همان دسته بندی)
        $suggestions = Post::where('is_editor_pick', true)
            ->with(['admin', 'category'])
            ->published()
            ->latest()
            ->limit(8)
            ->get();

        // جدیدترین مقالات
        $latestPosts = Post::where('id', '!=', $post->id)
            ->with(['admin', 'category'])
            ->published()
            ->latest()
            ->limit(8)
            ->get();

        // هشتگ های داغ
        $hotTags = Tag::withCount('posts')
            ->orderBy('posts_count', 'desc')
            ->limit(20)
            ->get();

        // بررسی لایک کاربر (با جدول likes جدید)
        $isLiked = false;
        if (auth()->check() || request()->ip()) {
            $isLiked = Like::where('likeable_id', $post->id)
                ->where('likeable_type', Post::class)
                ->where('type', 'like')
                ->where(function($q) {
                    if (auth()->check()) {
                        $q->where('user_id', auth()->id());
                    } else {
                        $q->where('ip_address', request()->ip());
                    }
                })
                ->exists();
        }

        $userPendingComments = $post->comments()
            ->where('status', 'pending')
            ->whereNull('comment_id')
            ->where(function($query) {
                if (auth()->check()) {
                    $query->where('user_id', auth()->id());
                } else {
                    $query->where('session_id', session()->getId());
                }
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // پاسخ‌های در انتظار تایید کاربر
        $userPendingReplies = Comment::where('commentable_type', Post::class)
            ->where('commentable_id', $post->id)
            ->whereNotNull('comment_id')
            ->where('status', 'pending')
            ->where(function($q) {
                if (auth()->check()) {
                    $q->where('user_id', auth()->id());
                } else {
                    $q->where('session_id', session()->getId());
                }
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('comment_id');

        return view('front::articles.show', compact('post', 'suggestions', 'latestPosts', 'hotTags', 'isLiked', 'userPendingComments', 'userPendingReplies'));
    }

    /**
     * لایک/آنلایک مقاله (با جدول likes یکتا)
     */

    // لایک/دیسلایک پست
    public function likeToggle(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id'
        ]);

        $post = Post::find($request->post_id);
        $userName = auth()->check() ? (auth()->user()->full_name ?? auth()->user()->username) : 'کاربر مهمان';
        $postTitle = $post->title;

        // بررسی آیا کاربر قبلاً لایک کرده است
        $like = Like::where('likeable_id', $post->id)
            ->where('likeable_type', Post::class)
            ->where('type', 'like')
            ->where(function ($q) {
                if (auth()->check()) {
                    $q->where('user_id', auth()->id());
                } else {
                    $q->where('ip_address', request()->ip());
                }
            })
            ->first();

        if ($like) {
            // حذف لایک
            $like->delete();
            $liked = false;

            $description = "لایک مقاله «{$postTitle}» را لغو کرد";

            activity()
                ->performedOn($post)
                ->event('unliked')
                ->causedBy(auth()->user())
                ->withProperties([
                    'action' => 'unlike_post',
                    'post_title' => $postTitle,
                    'ip' => $request->ip()
                ])
                ->log($description);
        } else {
            // افزودن لایک
            Like::create([
                'likeable_id' => $post->id,
                'likeable_type' => Post::class,
                'type' => 'like',
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'session_id' => session()->getId(),
                'device_type' => $this->getDeviceType(),
                'is_guest' => auth()->id() ? false : true
            ]);
            $liked = true;

            $description = "مقاله «{$postTitle}» را لایک کرد";

            activity()
                ->performedOn($post)
                ->causedBy(auth()->user())
                ->event('liked')
                ->withProperties([
                    'action' => 'like_post',
                    'post_title' => $postTitle,
                    'ip' => $request->ip()
                ])
                ->log($description);
        }

        $likesCount = Like::where('likeable_id', $post->id)
            ->where('likeable_type', Post::class)
            ->where('type', 'like')
            ->count();

        return response()->json([
            'success' => true,
            'liked' => $liked,
            'likes_count' => $likesCount
        ]);
    }

    // ثبت دیدگاه جدید
    public function commentStore(Request $request, $slug)
    {
        $type = "post";
        $modelMap = [
            'post' => Post::class,
            'product' => Product::class,
        ];

        if (!isset($modelMap[$type])) {
            abort(404);
        }

        $modelClass = $modelMap[$type];
        $item = $modelClass::where('slug', $slug)->firstOrFail();

        $request->validate([
            'content' => 'required|string|min:3|max:1000',
        ]);

        $userName = auth()->check() ? (auth()->user()->full_name ?? auth()->user()->username) : 'کاربر مهمان';
        $itemTitle = $item->title ?? $item->name ?? "#{$item->id}";
        $itemType = $type == 'post' ? 'مقاله' : 'محصول';

        $comment = Comment::create([
            'body' => $request->input('content'),
            'commentable_id' => $item->id,
            'commentable_type' => $modelClass,
            'user_id' => auth()->id(),
            'admin_id' => null,
            'comment_id' => null,
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'session_id' => session()->getId(),
            'user_agent' => $request->userAgent(),
            'likes_count' => 0,
            'dislikes_count' => 0
        ]);

        $commentText = mb_substr($comment->body, 0, 50);

        $description = "نظر «{$commentText}» را در {$itemType} «{$itemTitle}» ثبت کرد";

        activity()
            ->performedOn($comment)
            ->causedBy(auth()->user())
            ->event('created')
            ->withProperties([
                'action' => 'add_comment',
                'comment_text' => $commentText,
                'item_type' => $itemType,
                'item_title' => $itemTitle,
                'item_id' => $item->id,
                'ip' => $request->ip(),
                'status' => 'pending'
            ])
            ->log($description);

        if (!auth()->check()) {
            $pendingComments = session()->get('pending_comments', []);
            $pendingComments[] = $comment->id;
            session()->put('pending_comments', $pendingComments);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'دیدگاه شما با موفقیت ثبت شد و بعد از تایید در سایت نمایش داده میشود.',
                'comment' => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'created_at' => jdate($comment->created_at)->format('d F Y'),
                    'user' => auth()->check() ? [
                        'full_name' => auth()->user()->full_name,
                        'image_url' => auth()->user()->image_url
                    ] : null,
                    'is_pending' => true
                ]
            ]);
        }

        return back()->with('success', 'دیدگاه شما با موفقیت ثبت شد');
    }

    // ثبت پاسخ به دیدگاه
    public function commentReply(Request $request)
    {
        $request->validate([
            'comment_id' => 'required|exists:comments,id',
            'content' => 'required|string|min:3|max:1000'
        ]);

        $parentComment = Comment::findOrFail($request->comment_id);

        $itemType = $parentComment->commentable_type === Post::class ? 'مقاله' : 'محصول';
        $itemTitle = $parentComment->commentable->title ?? $parentComment->commentable->name ?? '#' . $parentComment->commentable_id;
        $parentCommentText = mb_substr($parentComment->body, 0, 50);

        if ($parentComment->user) {
            $parentCommentUserName = $parentComment->user->full_name;
        } elseif ($parentComment->admin) {
            $parentCommentUserName = $parentComment->admin->full_name;
        } else {
            $parentCommentUserName = 'کاربر';
        }

        $comment = Comment::create([
            'body' => $request->input('content'),
            'commentable_id' => $parentComment->commentable_id,
            'commentable_type' => $parentComment->commentable_type,
            'user_id' => auth()->id(),
            'admin_id' => null,
            'comment_id' => $parentComment->id,
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'session_id' => session()->getId(),
            'user_agent' => $request->userAgent(),
            'likes_count' => 0,
            'dislikes_count' => 0
        ]);

        $replyText = mb_substr($comment->body, 0, 50);

        $description = "به نظر «{$parentCommentText}» در {$itemType} «{$itemTitle}» پاسخ داد: «{$replyText}»";

        activity()
            ->performedOn($comment)
            ->causedBy(auth()->user())
            ->event('replied')
            ->withProperties([
                'action' => 'reply_to_comment',
                'reply_text' => $replyText,
                'parent_comment_id' => $parentComment->id,
                'parent_comment_text' => $parentCommentText,
                'parent_comment_user' => $parentCommentUserName,
                'item_type' => $itemType,
                'item_title' => $itemTitle,
                'ip' => $request->ip(),
                'status' => 'pending'
            ])
            ->log($description);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'پاسخ شما به ' . $parentCommentUserName . ' با موفقیت ثبت شد و بعد از تایید در سایت نمایش داده میشود.',
                'comment' => [
                    'parent_id' => $parentComment->id,
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'created_at' => jdate($comment->created_at)->format('d F Y'),
                    'user' => auth()->check() ? [
                        'full_name' => auth()->user()->full_name,
                        'image_url' => auth()->user()->image_url
                    ] : null,
                    'is_pending' => true
                ]
            ]);
        }

        return back()->with('success', 'پاسخ شما با موفقیت ثبت شد');
    }

    // لایک/دیسلایک دیدگاه
    public function commentLike(Request $request, $commentId)
    {
        $request->validate([
            'type' => 'required|in:like,dislike'
        ]);

        $comment = Comment::findOrFail($commentId);

        $itemType = $comment->commentable_type === Post::class ? 'مقاله' : 'محصول';
        $itemTitle = $comment->commentable->title ?? $comment->commentable->name ?? '#' . $comment->commentable_id;
        $commentText = mb_substr($comment->body, 0, 50);
        $actionType = $request->type == 'like' ? 'لایک' : 'دیسلایک';

        $likedComments = $request->cookie('comment_likes', '[]');
        $likedComments = json_decode($likedComments, true);

        $key = $commentId . '_' . $request->type;
        $liked = false;

        if (isset($likedComments[$key])) {
            unset($likedComments[$key]);
            if ($request->type == 'like') {
                $comment->decrement('likes_count');
            } else {
                $comment->decrement('dislikes_count');
            }
            $liked = false;

            $description = "{$actionType} نظر «{$commentText}» در {$itemType} «{$itemTitle}» را لغو کرد";

            activity()
                ->performedOn($comment)
                ->causedBy(auth()->user())
                ->event('unliked')
                ->withProperties([
                    'action' => 'unlike_comment',
                    'comment_text' => $commentText,
                    'item_type' => $itemType,
                    'item_title' => $itemTitle,
                    'type' => $request->type
                ])
                ->log($description);
        } else {
            $oppositeKey = $commentId . '_' . ($request->type == 'like' ? 'dislike' : 'like');
            if (isset($likedComments[$oppositeKey])) {
                unset($likedComments[$oppositeKey]);
                if ($request->type == 'like') {
                    $comment->decrement('dislikes_count');
                } else {
                    $comment->decrement('likes_count');
                }
            }
            $likedComments[$key] = [
                'type' => $request->type,
                'timestamp' => time()
            ];
            if ($request->type == 'like') {
                $comment->increment('likes_count');
            } else {
                $comment->increment('dislikes_count');
            }
            $liked = true;

            $description = "به نظر «{$commentText}» در {$itemType} «{$itemTitle}» {$actionType} داد";

            activity()
                ->performedOn($comment)
                ->causedBy(auth()->user())
                ->event($request->type == 'like' ? 'liked' : 'disliked')
                ->withProperties([
                    'action' => $request->type . '_comment',
                    'comment_text' => $commentText,
                    'item_type' => $itemType,
                    'item_title' => $itemTitle,
                    'type' => $request->type,
                    'ip' => $request->ip()
                ])
                ->log($description);
        }

        $cookie = cookie('comment_likes', json_encode($likedComments), 60 * 24 * 365);
        $comment->refresh();

        return response()->json([
            'success' => true,
            'liked' => $liked,
            'type' => $request->type,
            'likes_count' => $comment->likes_count,
            'dislikes_count' => $comment->dislikes_count
        ])->withCookie($cookie);
    }

    private function getDeviceType()
    {
        $userAgent = request()->userAgent();
        if (preg_match('/(mobile|android|iphone|ipod|blackberry|windows phone)/i', $userAgent)) return 'mobile';
        if (preg_match('/(ipad|tablet)/i', $userAgent)) return 'tablet';
        return 'desktop';
    }

    // دریافت نظرات یک مقاله
    public function getComments($postId)
    {
        // سانیار فاز K: در WeblakShop این متد با status='approved' فیلتر می‌کرد،
        // در حالی که واژگان وضعیت کامنت در کل سیستم 'accepted' است → لیست AJAX
        // نظرات حتی با وجود کامنت تاییدشده همیشه خالی برمی‌گشت.
        $comments = Comment::where('commentable_id', $postId)
            ->where('commentable_type', Post::class)
            ->whereNull('comment_id')
            ->where('status', 'accepted')
            ->with(['user', 'admin', 'replies' => function($q) {
                $q->where('status', 'accepted')
                    ->with(['user', 'admin'])
                    ->orderBy('created_at', 'asc');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        if (request()->ajax()) {
            return response()->json([
                'data' => view('front::articles.partials.comments-list', compact('comments'))->render(),
                'pagination' => view('front::articles.partials.comments-pagination', compact('comments'))->render(),
            ]);
        }

        return $comments;
    }


}
