<?php

namespace Themes\SaniarTheme\src\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Product;
use App\Models\Review;
use App\Notifications\Post\CommentPostCreated;
use App\Notifications\Product\CommentProductCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = auth()
            ->user()
            ->reviews()
            ->latest()
            ->paginate(20);

        return view('front::reviews.index', compact('reviews'));
    }

    public function show(Product $product)
    {
        $review = $product->reviews()->with('points')->with('product')->where('user_id', auth()->user()->id)->first();
        return response()->json(['review' => $review]);
    }

    public function store(Request $request)
    {
        $product = Product::findOrFail($request->product_id);
        $data = $this->validate($request, [
            'title'       => 'required|string',
            'body'        => 'required|string|max:1000',
            'rating'      => 'required|integer|between:1,5',
        ],[
            'rating.between'=>' فیلد امتیاز نمی‌تواند خالی باشد'
        ]);

        if ($request->user()->hasBoughtProduct($product)) {
            $request->validate([
                'suggest'     => 'required|in:yes,no,not_sure',
            ]);

            $data['suggest'] = $request->suggest;
        }

        $data['status'] = 'pending';

        $review = $product->reviews()->updateOrCreate(
            [
                'user_id' => auth()->user()->id
            ],
            $data
        );

        $review->points()->delete();

        $advantages = $request->input('review.advantages');
        $advantagesList = [];
        if ($advantages) {
            foreach ($advantages as $advantage) {
                $review->points()->create([
                    'text' => $advantage,
                    'type' => 'positive',
                ]);
                $advantagesList[] = $advantage;
            }
        }

        $disadvantages = $request->input('review.disadvantages');
        $disadvantagesList = [];
        if ($disadvantages) {
            foreach ($disadvantages as $advantage) {
                $review->points()->create([
                    'text' => $advantage,
                    'type' => 'negative',
                ]);
                $disadvantagesList[] = $advantage;
            }
        }

        $product->refreshRating();
        $admins = Admin::all();
        Notification::send($admins, new CommentProductCreated($review));

        // ثبت لاگ
        $adminName = auth()->user()->full_name ?? auth()->user()->name ?? 'کاربر';
        $productTitle = $product->title ?? "#{$product->id}";
        $action = $review->wasRecentlyCreated ? 'create_review' : 'update_review';

        $attributes = [
            'عنوان' => $data['title'],
            'متن' => mb_substr($data['body'], 0, 100),
            'امتیاز' => $data['rating'] . ' از 5',
        ];

        if (isset($data['suggest'])) {
            $suggestText = match($data['suggest']) {
                'yes' => 'بله',
                'no' => 'خیر',
                'not_sure' => 'مطمئن نیستم',
                default => $data['suggest']
            };
            $attributes['توصیه به خرید'] = $suggestText;
        }

        if (!empty($advantagesList)) {
            $attributes['نقاط قوت'] = implode('، ', $advantagesList);
        }

        if (!empty($disadvantagesList)) {
            $attributes['نقاط ضعف'] = implode('، ', $disadvantagesList);
        }

        activity()
            ->performedOn($product)
            ->causedBy(auth()->user())
            ->event($review->wasRecentlyCreated ? 'created' : 'updated')
            ->withProperties([
                'action' => $action,
                'product_title' => $productTitle,
                'product_id' => $product->id,
                'review_id' => $review->id,
                'attributes' => $attributes,
                'status' => 'pending',
                'ip' => request()->ip()
            ])
            ->log("{$adminName} نظر خود را برای محصول «{$productTitle}» ثبت کرد");

        return response('success');
    }

    public function like(Review $review)
    {
        $like = $review->likes()->updateOrCreate(
            [
                'user_id' => auth()->user()->id
            ],
            [
                'type' => 'like'
            ],
        );

        $review->refreshLikesCount();

        // ثبت لاگ لایک نظر
        $userName = auth()->user()->full_name ?? auth()->user()->name ?? 'کاربر';
        $productTitle = $review->product->title ?? "#{$review->product_id}";
        $reviewExcerpt = mb_substr($review->body, 0, 50);

        activity()
            ->performedOn($review)
            ->causedBy(auth()->user())
            ->event('liked')
            ->withProperties([
                'action' => 'like_review',
                'product_title' => $productTitle,
                'product_id' => $review->product_id,
                'review_id' => $review->id,
                'review_excerpt' => $reviewExcerpt,
                'ip' => request()->ip()
            ])
            ->log("{$userName} به نظر «{$reviewExcerpt}» برای محصول «{$productTitle}» لایک داد");

        return response()->json(['review' => $review]);
    }

    public function dislike(Review $review)
    {
        $dislike = $review->likes()->updateOrCreate(
            [
                'user_id' => auth()->user()->id
            ],
            [
                'type' => 'dislike'
            ],
        );

        $review->refreshLikesCount();

        // ثبت لاگ دیسلایک نظر
        $userName = auth()->user()->full_name ?? auth()->user()->name ?? 'کاربر';
        $productTitle = $review->product->title ?? "#{$review->product_id}";
        $reviewExcerpt = mb_substr($review->body, 0, 50);

        activity()
            ->performedOn($review)
            ->causedBy(auth()->user())
            ->event('disliked')
            ->withProperties([
                'action' => 'dislike_review',
                'product_title' => $productTitle,
                'product_id' => $review->product_id,
                'review_id' => $review->id,
                'review_excerpt' => $reviewExcerpt,
                'ip' => request()->ip()
            ])
            ->log("{$userName} به نظر «{$reviewExcerpt}» برای محصول «{$productTitle}» دیسلایک داد");

        return response()->json(['review' => $review]);
    }

}
