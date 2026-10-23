<?php
// app/Http/Controllers/Front/StoryController.php

namespace Themes\Saniar\src\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Story;
use App\Models\StoryComment;
use App\Models\StoryInteraction;
use App\Models\StoryLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Modules\Story\Traits\StoryInteractionTrait;

class StoryController extends Controller
{
    use StoryInteractionTrait;

    /**
     * دریافت محتوای استوری (برای AJAX)
     */
    public function getStoryContent($id)
    {
        $story = Story::with(['product'])->findOrFail($id);

        if ($story->expiry_date < now()) {
            return response()->json(['error' => 'Story expired'], 404);
        }

        $commentsCount = $story->comments()->where('status', 'approved')->count();

        // ثبت بازدید
        $request = new Request(['story_id' => $story->id]);
        $this->setStorySeen($request);

        // ثبت تعامل باز شدن استوری
        $this->logStoryOpen($story->id);

        return view('front::partials.stories.story-content-template', compact('story', 'commentsCount'))->render();
    }

    /**
     * ثبت بازدید استوری
     */
    public function setStorySeen(Request $request)
    {
        try {
            $request->validate([
                'story_id' => 'required|exists:stories,id'
            ]);

            $storyId = $request->story_id;
            $story = Story::find($storyId);

            if (!$story || $story->expiry_date < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'استوری یافت نشد یا منقضی شده است'
                ], 404);
            }

            // ثبت بازدید کل در جدول استوری
            $story->increment('views_count');

            // بررسی کوکی برای بازدید واقعی
            $storySeen = Cookie::get('story_seen', '[]');
            $storySeen = json_decode($storySeen, true) ?: [];

            $isRealView = false;
            if (!in_array($storyId, $storySeen)) {
                $storySeen[] = $storyId;
                $story->increment('real_views_count');
                Cookie::queue('story_seen', json_encode($storySeen), 10080);
                $isRealView = true;
            }

            // ثبت تعامل بازدید با استفاده از Trait
            $interaction = $this->logStoryView($storyId, $isRealView, $story->views_count, $story->real_views_count);

            $story->refresh();

            return response()->json([
                'success' => true,
                'message' => 'بازدید با موفقیت ثبت شد',
                'data' => [
                    'views_count' => $story->views_count,
                    'real_views_count' => $story->real_views_count,
                    'is_real_view' => $isRealView,
                    'likes_count' => $story->likes_count,
                    'interaction_count' => $interaction->count ?? 1
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ثبت بازدید: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * لایک/آنلایک استوری
     */
    public function toggleLike(Request $request)
    {
        try {
            $request->validate([
                'story_id' => 'required|exists:stories,id',
                'is_liked' => 'required|boolean'
            ]);

            $storyId = $request->story_id;
            $userId = auth()->id();
            $sessionId = session()->getId();
            $ipAddress = $request->ip();
            $userAgent = $request->userAgent();
            $deviceType = $this->getDeviceType($userAgent);

            $result = $userId
                ? $this->handleAuthenticatedUser($storyId, $userId, $request->is_liked)
                : $this->handleGuestUser($storyId, $sessionId, $ipAddress, $userAgent, $deviceType, $request->is_liked);

            // ثبت تعامل لایک با استفاده از Trait
            $this->logStoryLike($storyId, $request->is_liked);

            return $result;

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ثبت تعاملات (کلیک روی ویجت، محصول و ...)
     */
    public function storeInteraction(Request $request)
    {
        try {
            $request->validate([
                'story_id' => 'required|exists:stories,id',
                'type' => 'required|string|in:widget_click,product_click,share,like_button,view_full,call_to_action,external_link,progress_25,progress_50,progress_75,progress_100'
            ]);

            $story = Story::find($request->story_id);

            if (!$story || $story->expiry_date < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'استوری یافت نشد'
                ], 404);
            }

            // ثبت تعامل با استفاده از Trait بر اساس نوع
            switch ($request->type) {
                case 'widget_click':
                    $this->logStoryWidgetClick($request->story_id, $request->element_text, $request->target_url);
                    $story->increment('widget_clicks_count');
                    break;
                case 'product_click':
                    $this->logStoryProductClick($request->story_id, $request->element_id, $request->element_text, $request->target_url);
                    $story->increment('product_clicks_count');
                    break;
                case 'share':
                    $this->logStoryShare($request->story_id, $request->platform);
                    break;
                default:
                    $this->logOrUpdateStoryInteraction($request->story_id, $request->type, [
                        'additional_data' => $request->additional_data
                    ]);
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'تعامل با موفقیت ثبت شد'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ثبت تعامل: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * دریافت کامنت‌های استوری
     */
    public function getStoryComments($storyId)
    {
        $story = Story::findOrFail($storyId);

        $comments = $story->comments()
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $html = view('front::partials.stories.story-comments-list-template', compact('comments', 'story'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'total' => $story->comments()->where('status', 'approved')->count()
        ]);
    }

    /**
     * ثبت کامنت جدید
     */
    public function storeStoryComment(Request $request)
    {
        $request->validate([
            'story_id' => 'required|exists:stories,id',
            'comment' => 'required|string|min:1|max:500'
        ]);

        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'هنوز وارد حساب کاربری خود نشده اید'
            ], 404);
        }

        $story = Story::find($request->story_id);

        if (!$story || $story->expiry_date < now()) {
            return response()->json([
                'success' => false,
                'message' => 'استوری یافت نشد یا منقضی شده است'
            ], 404);
        }

        $comment = StoryComment::create([
            'story_id' => $request->story_id,
            'user_id' => auth()->id(),
            'comment' => $request->comment,
            'ip_address' => $request->ip(),
            'status' => 'pending'
        ]);

        // ثبت تعامل کامنت با استفاده از Trait
        $interaction = $this->logStoryComment($request->story_id, $request->comment, $comment->id);

        return response()->json([
            'success' => true,
            'message' => 'کامنت شما با موفقیت ثبت شد و بعد از تایید نمایش داده میشود!',
            'comment' => [
                'id' => $comment->id,
                'name' => auth()->user()->fullname ?? auth()->user()->name ?? 'کاربر',
                'avatar' => auth()->user()->image_url ?? asset('default-avatar.jpg'),
                'comment' => '<span class="badge badge-warning">بعد از تایید نمایش داده میشود!</span>',
                'created_at' => $comment->created_at->diffForHumans(),
                'is_user' => true,
            ],
            'interaction_count' => $interaction->count ?? 1
        ]);
    }

    /**
     * ثبت تعامل کلیک روی ویجت (API)
     */
    public function logWidgetClick(Request $request)
    {
        $request->validate([
            'story_id' => 'required|exists:stories,id',
            'widget_title' => 'required|string',
            'widget_url' => 'required|url'
        ]);

        $interaction = $this->logStoryWidgetClick($request->story_id, $request->widget_title, $request->widget_url);

        return response()->json([
            'success' => true,
            'count' => $interaction->count ?? 1
        ]);
    }

    /**
     * ثبت تعامل کلیک روی محصول (API)
     */
    public function productRedirect(Story $story, Product $product)
    {
        $sessionKey = "product_clicked_{$story->id}_{$product->id}";
        $productUrl = route('front.products.show', $product->slug);

        // جلوگیری از ثبت دوباره
        if (session()->has($sessionKey)) {
            return redirect()->to($productUrl, 301);
        }

        session()->put($sessionKey, true);

        // ثبت تعامل
        $this->logStoryProductClick($story->id, $product->id, $product->title, $productUrl);
        $story->increment('product_clicks_count');

        // ریدایرکت 301
        return redirect()->to($productUrl, 301);
    }

    /**
     * ریدایرکت ویجت با ثبت تعامل
     */
    public function widgetRedirect(Story $story)
    {
        $sessionKey = "widget_clicked_{$story->id}";
        $url = $story->widget_link;

        if (session()->has($sessionKey)) {
            return $this->smartRedirect($url, 302);
        }

        session()->put($sessionKey, true);

        $this->logStoryWidgetClick($story->id, $story->widget_title, $url);

        return $this->smartRedirect($url, 302);
    }

    private function smartRedirect($url, $statusCode = 301)
    {
        $url = trim($url);

        // آدرس خارجی
        if (preg_match('/^(https?:\/\/|\/\/)/i', $url)) {
            return redirect()->away($url, $statusCode);
        }

        // آدرس داخلی
        if (str_starts_with($url, '/')) {
            return redirect()->to($url, $statusCode);
        }

        return redirect()->to('/' . $url, $statusCode);
    }
    /**
     * ثبت تعامل اشتراک‌گذاری (API)
     */
    public function logShare(Request $request)
    {
        $request->validate([
            'story_id' => 'required|exists:stories,id',
            'platform' => 'nullable|string'
        ]);

        $interaction = $this->logStoryShare($request->story_id, $request->platform);

        return response()->json([
            'success' => true,
            'count' => $interaction->count ?? 1
        ]);
    }

    /**
     * دریافت آمار تعاملات استوری (API)
     */
    public function getStoryStats($storyId)
    {
        $story = Story::findOrFail($storyId);

        $stats = $this->getStoryInteractionStats($storyId);

        return response()->json([
            'success' => true,
            'data' => [
                'story_id' => $storyId,
                'title' => $story->title,
                'views_count' => $story->views_count,
                'real_views_count' => $story->real_views_count,
                'likes_count' => $story->likes_count,
                'comments_count' => $story->comments()->where('status', 'approved')->count(),
                'interactions' => $stats
            ]
        ]);
    }

    // ========== متدهای خصوصی ==========

    private function handleAuthenticatedUser($storyId, $userId, $isLiked)
    {
        $story = Story::findOrFail($storyId);
        $existingLike = StoryLike::where('story_id', $storyId)
            ->where('user_id', $userId)
            ->first();

        if ($isLiked && !$existingLike) {
            StoryLike::create([
                'story_id' => $storyId,
                'user_id' => $userId,
                'is_guest' => false,
                'ip_address' => request()->ip(),
                'session_id' => session()->getId(),
                'user_agent' => request()->userAgent(),
                'device_type' => $this->getDeviceType(request()->userAgent())
            ]);
            $newLikesCount = $story->likes_count + 1;
            $story->update(['likes_count' => $newLikesCount]);

            return response()->json([
                'success' => true,
                'is_liked' => true,
                'likes_count' => $newLikesCount
            ]);

        } elseif (!$isLiked && $existingLike) {
            $existingLike->delete();
            $newLikesCount = max(0, $story->likes_count - 1);
            $story->update(['likes_count' => $newLikesCount]);

            return response()->json([
                'success' => true,
                'is_liked' => false,
                'likes_count' => $newLikesCount
            ]);
        }

        return response()->json([
            'success' => true,
            'is_liked' => $existingLike ? true : false,
            'likes_count' => $story->likes_count
        ]);
    }

    private function handleGuestUser($storyId, $sessionId, $ipAddress, $userAgent, $deviceType, $isLiked)
    {
        $story = Story::findOrFail($storyId);
        $existingLike = StoryLike::where('story_id', $storyId)
            ->where('session_id', $sessionId)
            ->where('is_guest', true)
            ->first();

        if ($isLiked && !$existingLike) {
            StoryLike::create([
                'story_id' => $storyId,
                'session_id' => $sessionId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'device_type' => $deviceType,
                'is_guest' => true
            ]);
            $newLikesCount = $story->likes_count + 1;
            $story->update(['likes_count' => $newLikesCount]);

            return response()->json([
                'success' => true,
                'is_liked' => true,
                'likes_count' => $newLikesCount
            ]);

        } elseif (!$isLiked && $existingLike) {
            $existingLike->delete();
            $newLikesCount = max(0, $story->likes_count - 1);
            $story->update(['likes_count' => $newLikesCount]);

            return response()->json([
                'success' => true,
                'is_liked' => false,
                'likes_count' => $newLikesCount
            ]);
        }

        return response()->json([
            'success' => true,
            'is_liked' => $existingLike ? true : false,
            'likes_count' => $story->likes_count
        ]);
    }

    private function getDeviceType($userAgent)
    {
        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent)) {
            return 'mobile';
        } elseif (preg_match('/iPad|Tablet|PlayBook|Kindle|Silk/i', $userAgent)) {
            return 'tablet';
        }
        return 'desktop';
    }
}
