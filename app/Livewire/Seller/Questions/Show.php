<?php

namespace App\Livewire\Seller\Questions;

use App\Models\Comment;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — جزئیات پرسش و فرم پاسخ (پورت QuestionSellerController@show + update).
 *
 * گارد مالکیت: پرسش باید کامنتِ یکی از محصولات این فروشنده باشد
 * (پنل قدیم این گارد را نداشت — طبق قرارداد فاز ۱۰۸ گارد اضافه شد).
 *
 * فرم پاسخ: کلاسیک PUT به seller.questions.update با همان فیلدهای قدیمی
 * (body / replay) — کنترلر قدیمی response($question) می‌دهد، پس ارسال با
 * fetch و در موفقیت بازگشت به فهرست انجام می‌شود (هم‌الگوی index.js قدیمی).
 */
#[Layout('sellers.layouts.panel')]
#[Title('مشاهده پرسش')]
class Show extends Component
{
    public Comment $question;

    public function mount(Comment $question): void
    {
        // گارد مالکیت — کامنتِ محصولِ دیگر فروشندگان ۴۰۴ است
        $belongsToSeller = Comment::query()
            ->where('comments.id', $question->id)
            ->where('comments.commentable_type', (new Product)->getMorphClass())
            ->whereIn('comments.commentable_id', seller()->products()->select('products.id'))
            ->exists();

        if (! $belongsToSeller) {
            abort(404);
        }

        $this->question = $question;
    }

    public function render()
    {
        $question = $this->question->load(['user:id,first_name,last_name,username', 'admin:id', 'seller:id,email', 'comments', 'commentable']);

        return view('sellers.panel.questions.show', [
            'question' => $question,
            'indexUrl' => route('seller.questions.index'),
            'updateUrl' => route('seller.questions.update', ['question' => $question]),
            'productUrl' => $question->commentable ? $question->commentable->link() : '#',
        ]);
    }
}
