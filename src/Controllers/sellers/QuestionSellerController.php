<?php

namespace Themes\SaniarTheme\src\Controllers\sellers;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Product;
use Illuminate\Http\Request;

class QuestionSellerController extends Controller
{
    public function index(Request $request)
    {
        // سانیار فاز J: حذف حلقهٔ N+1 لایه‌لایهٔ WeblakShop (محصول×کامنت) با یک whereIn
        $productIds = seller()->products()->pluck('products.id');

        $questions = Comment::where('commentable_type', Product::class)
            ->whereIn('commentable_id', $productIds)
            ->filter($request)
            ->paginate(20);

        // شمارنده از ->total() خود Paginator خوانده می‌شود (حذف کوئری تکراری)
        return view('front::sellers.panel.questions.index', compact('questions'));
    }

    public function show(Comment $question)
    {
        $this->assertSellerQuestion($question);

        // قرارداد AJAX: HTML رندرشده برای تزریق در مودال
        return view('front::sellers.panel.questions.show', compact('question'))->render();
    }

    public function update(Comment $question, Request $request)
    {
        $this->assertSellerQuestion($question);

        $this->validate($request, [
            'body'   => 'required',
            'replay' => 'nullable|string',
        ]);

        if ($question->comment_id != "") {
            $question->update([
                'body'   => $request->body,
                'status' => 'pending',
            ]);
        } else {
            $question->update([
                'body' => $request->body,
            ]);
        }

        if ($request->replay) {
            // سانیار فاز J: ستون seller_id روی comments وجود ندارد (الگوی میزبان: پاسخ کاربر=user_id، پاسخ ادمین=admin_id) —
            // WeblakShop با seller_id می‌ساخت → SQL 500 در هر پاسخ فروشنده. پاسخِ فروشنده با comment_id ثبت می‌شود
            // و مثل پاسخ کاربر تا تأیید ادمین (پیش‌فرض pending) منتظر بررسی می‌ماند.
            $question->commentable->comments()->create([
                'body'       => $request->replay,
                'comment_id' => $question->id,
            ]);
            $question->update([
                'status' => 'accepted',
            ]);
        }

        return response($question);
    }

    /**
     * سانیار فاز J — گارد IDOR:
     * در WeblakShop هر فروشنده‌ای می‌توانست با تغییر id، پرسش/پاسخ محصول فروشندهٔ دیگری را ببیند/ویرایش کند.
     */
    private function assertSellerQuestion(Comment $comment)
    {
        $productIds = seller()->products()->pluck('products.id');
        abort_unless(
            $comment->commentable_type === Product::class
            && $productIds->contains($comment->commentable_id),
            404
        );
    }
}
