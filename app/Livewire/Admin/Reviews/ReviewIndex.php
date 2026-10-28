<?php

namespace App\Livewire\Admin\Reviews;

use App\Models\Review;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * مدیریت نظرات محصولات — پورت ۱:۱ Back\ReviewController به Livewire
 * دسترسی: products.reviews (middleware can قدیمی)
 */
#[Title('مدیریت نظرات')]
class ReviewIndex extends Component
{
    use WithPagination;

    public int $perPage = 15;

    /* ---------------- فیلترها (State در URL) — پورت Review::scopeFilter ---------------- */

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'ordering', except: 'latest')]
    public string $ordering = 'latest';

    /* ---------------- مودال جزئیات (پورت show قدیمی) ---------------- */

    public bool $showDetail = false;

    public ?int $viewingReview = null;

    /* ---------------- مودال ویرایش (پورت update کامل با نقاط قوت/ضعف) ---------------- */

    public bool $showEdit = false;

    public ?int $editingReview = null;

    public string $editTitle = '';

    public string $editBody = '';

    public int $editRating = 5;

    public string $editSuggest = '';

    public string $editStatus = 'pending';

    /** نقاط قوت (chips متنی) */
    public array $advantages = [];

    /** نقاط ضعف (chips متنی) */
    public array $disadvantages = [];

    public string $advantageDraft = '';

    public string $disadvantageDraft = '';

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('products.reviews') ?: abort(403);
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['status', 'ordering', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function applyFilters(): void
    {
        $this->status = '';
        $this->ordering = 'latest';
        $this->resetPage();
    }

    /* ---------------- لیست (پورت index قدیمی: Review::filter()->paginate(20)) ---------------- */

    /** پورت دقیق Review::scopeFilter (request → پراپرتی‌های #[Url]) */
    protected function baseQuery()
    {
        return Review::query()
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            // پورت switch(ordering) قدیمی
            ->when($this->ordering === 'oldest', fn ($query) => $query->oldest(), fn ($query) => $query->latest())
            ->with(['user', 'product', 'points', 'seller.seller_info']);
    }

    #[Computed]
    public function reviews()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    #[Computed(persist: true, seconds: 60)]
    public function pendingCount(): int
    {
        return Review::query()->where('status', 'pending')->count();
    }

    /* ---------------- مودال جزئیات (پورت show قدیمی) ---------------- */

    public function openDetail(int $id): void
    {
        auth('adminPanel')->user()?->can('products.reviews') ?: abort(403);

        if (! Review::find($id)) {
            $this->dispatch('toast', message: 'نظر یافت نشد.', type: 'error');

            return;
        }

        $this->viewingReview = $id;
        $this->showDetail = true;
    }

    #[Computed]
    public function detailReview(): ?Review
    {
        if (! $this->viewingReview) {
            return null;
        }

        return Review::query()
            ->with(['user', 'product', 'points'])
            ->find($this->viewingReview);
    }

    /* ---------------- مودال ویرایش (پورت update کامل) ---------------- */

    public function openEdit(int $id): void
    {
        auth('adminPanel')->user()?->can('products.reviews') ?: abort(403);

        if (! $review = Review::query()->with('points')->find($id)) {
            $this->dispatch('toast', message: 'نظر یافت نشد.', type: 'error');

            return;
        }

        $this->editingReview = $review->id;
        $this->editTitle = (string) $review->title;
        $this->editBody = (string) $review->body;
        $this->editRating = (int) $review->rating;
        $this->editSuggest = (string) ($review->suggest ?? '');
        $this->editStatus = (string) $review->status;
        $this->advantages = $review->points->where('type', 'positive')->pluck('text')->values()->all();
        $this->disadvantages = $review->points->where('type', 'negative')->pluck('text')->values()->all();
        $this->advantageDraft = '';
        $this->disadvantageDraft = '';
        $this->showEdit = true;
    }

    /* ---------------- چیپ‌پیکر نقاط قوت/ضعف (الگوی چیپ‌پیکر DiscountForm) ---------------- */

    public function addAdvantage(): void
    {
        $text = trim($this->advantageDraft);
        $this->advantageDraft = '';

        if ($text === '') {
            return;
        }

        if (! in_array($text, $this->advantages, true)) {
            $this->advantages[] = $text;
        }
    }

    public function removeAdvantage(int $index): void
    {
        unset($this->advantages[$index]);
        $this->advantages = array_values($this->advantages);
    }

    public function addDisadvantage(): void
    {
        $text = trim($this->disadvantageDraft);
        $this->disadvantageDraft = '';

        if ($text === '') {
            return;
        }

        if (! in_array($text, $this->disadvantages, true)) {
            $this->disadvantages[] = $text;
        }
    }

    public function removeDisadvantage(int $index): void
    {
        unset($this->disadvantages[$index]);
        $this->disadvantages = array_values($this->disadvantages);
    }

    /* ---------------- ذخیره ویرایش (پورت ۱:۱ update قدیمی) ---------------- */

    public function updateReview(): void
    {
        auth('adminPanel')->user()?->can('products.reviews') ?: abort(403);

        if (! $review = Review::find($this->editingReview)) {
            $this->dispatch('toast', message: 'نظر یافت نشد.', type: 'error');

            return;
        }

        $this->validate([
            'editTitle'        => 'required|string',
            'editBody'         => 'required|string|max:1000',
            'editRating'       => 'required|between:1,5',
            'editSuggest'      => 'nullable|in:yes,no,not_sure',
            'editStatus'       => 'in:pending,accepted,rejected',
            'advantages'       => 'present|array',
            'advantages.*'     => 'string',
            'disadvantages'    => 'present|array',
            'disadvantages.*'  => 'string',
        ], [], [
            'editTitle'    => 'عنوان',
            'editBody'     => 'متن دیدگاه',
            'editRating'   => 'امتیاز',
            'editSuggest'  => 'پیشنهاد',
            'editStatus'   => 'وضعیت',
            'advantages'   => 'نقاط قوت',
            'disadvantages' => 'نقاط ضعف',
        ]);

        // ذخیره مقادیر قدیمی برای لاگ — پورت دقیق
        $oldTitle = $review->title;
        $oldBody = $review->body;
        $oldRating = $review->rating;
        $oldStatus = $review->status;
        $oldSuggest = $review->suggest;

        $newSuggest = $this->editSuggest !== '' ? $this->editSuggest : null;

        $review->update([
            'title'   => $this->editTitle,
            'body'    => $this->editBody,
            'rating'  => $this->editRating,
            'suggest' => $newSuggest,
            'status'  => $this->editStatus,
        ]);

        // ذخیره نقاط قوت و ضعف قبلی
        $oldAdvantages = $review->points()->where('type', 'positive')->pluck('text')->toArray();
        $oldDisadvantages = $review->points()->where('type', 'negative')->pluck('text')->toArray();

        $review->points()->delete();

        $advantagesList = [];

        foreach ($this->advantages as $advantage) {
            $review->points()->create([
                'text' => $advantage,
                'type' => 'positive',
            ]);
            $advantagesList[] = $advantage;
        }

        $disadvantagesList = [];

        foreach ($this->disadvantages as $disadvantage) {
            $review->points()->create([
                'text' => $disadvantage,
                'type' => 'negative',
            ]);
            $disadvantagesList[] = $disadvantage;
        }

        $review->product->refreshRating();

        // ثبت لاگ ویرایش نظر — پورت دقیق ساختار oldData/newData
        $adminName = $this->adminName();
        $productTitle = $review->product->title ?? "#{$review->product_id}";
        $reviewExcerpt = mb_substr($review->body, 0, 50);

        $oldData = [];
        $newData = [];

        if ($oldTitle != $review->title) {
            $oldData['عنوان'] = $oldTitle;
            $newData['عنوان'] = $review->title;
        }
        if ($oldBody != $review->body) {
            $oldData['متن'] = mb_substr($oldBody, 0, 50);
            $newData['متن'] = mb_substr($review->body, 0, 50);
        }
        if ($oldRating != $review->rating) {
            $oldData['امتیاز'] = $oldRating . ' از 5';
            $newData['امتیاز'] = $review->rating . ' از 5';
        }
        if ($oldStatus != $review->status) {
            $statusMap = ['pending' => 'در انتظار', 'accepted' => 'تایید شده', 'rejected' => 'رد شده'];
            $oldData['وضعیت'] = $statusMap[$oldStatus] ?? $oldStatus;
            $newData['وضعیت'] = $statusMap[$review->status] ?? $review->status;
        }
        if ($oldSuggest != $review->suggest) {
            $suggestMap = ['yes' => 'بله', 'no' => 'خیر', 'not_sure' => 'مطمئن نیستم'];
            $oldData['توصیه به خرید'] = $suggestMap[$oldSuggest] ?? $oldSuggest;
            $newData['توصیه به خرید'] = $suggestMap[$review->suggest] ?? $review->suggest;
        }

        // بررسی تغییرات نقاط قوت
        if (! empty($oldAdvantages) || ! empty($advantagesList)) {
            $oldAdvText = implode('، ', $oldAdvantages);
            $newAdvText = implode('، ', $advantagesList);
            if ($oldAdvText != $newAdvText) {
                $oldData['نقاط قوت'] = $oldAdvText ?: 'ندارد';
                $newData['نقاط قوت'] = $newAdvText ?: 'ندارد';
            }
        }

        // بررسی تغییرات نقاط ضعف
        if (! empty($oldDisadvantages) || ! empty($disadvantagesList)) {
            $oldDisText = implode('، ', $oldDisadvantages);
            $newDisText = implode('، ', $disadvantagesList);
            if ($oldDisText != $newDisText) {
                $oldData['نقاط ضعف'] = $oldDisText ?: 'ندارد';
                $newData['نقاط ضعف'] = $newDisText ?: 'ندارد';
            }
        }

        if (! empty($oldData)) {
            activity()
                ->performedOn($review->product)
                ->causedBy(auth('adminPanel')->user())
                ->event('updated')
                ->withProperties([
                    'action'        => 'update_review',
                    'product_title' => $productTitle,
                    'product_id'    => $review->product_id,
                    'review_id'     => $review->id,
                    'old'           => $oldData,
                    'attributes'    => $newData,
                    'ip'            => request()->ip(),
                ])
                ->log("{$adminName} نظر «{$reviewExcerpt}» را برای محصول «{$productTitle}» ویرایش کرد");
        }

        $this->closeEdit();

        $this->dispatch('toast', message: 'نظر با موفقیت به‌روزرسانی شد.', type: 'success');
    }

    public function closeEdit(): void
    {
        $this->showEdit = false;
        $this->editingReview = null;
        $this->reset('editTitle', 'editBody', 'editSuggest', 'advantageDraft', 'disadvantageDraft');
        $this->editRating = 5;
        $this->editStatus = 'pending';
        $this->advantages = [];
        $this->disadvantages = [];
    }

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('products.reviews') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('products.reviews') ?: abort(403);

        if (! $review = Review::find($this->confirmingDelete)) {
            $this->confirmingDelete = null;

            return;
        }

        $adminName = $this->adminName();
        $productTitle = $review->product->title ?? "#{$review->product_id}";
        $reviewExcerpt = mb_substr($review->body, 0, 50);
        $reviewId = $review->id;
        $productId = $review->product_id;
        $reviewTitle = $review->title;
        $rating = $review->rating;
        $product = $review->product;

        $review->delete();
        $product->refreshRating();

        // ثبت لاگ حذف نظر — پورت دقیق (event('deleted') و performedOn محصول)
        activity()
            ->performedOn($product)
            ->causedBy(auth('adminPanel')->user())
            ->event('deleted')
            ->withProperties([
                'action'         => 'delete_review',
                'product_title'  => $productTitle,
                'product_id'     => $productId,
                'review_id'      => $reviewId,
                'review_title'   => $reviewTitle,
                'review_excerpt' => $reviewExcerpt,
                'rating'         => $rating,
                'ip'             => request()->ip(),
            ])
            ->log("{$adminName} نظر «{$reviewExcerpt}» را برای محصول «{$productTitle}» حذف کرد");

        $this->confirmingDelete = null;

        if ($this->viewingReview !== null && ! Review::find($this->viewingReview)) {
            $this->showDetail = false;
            $this->viewingReview = null;
        }

        $this->dispatch('toast', message: 'نظر با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- ابزار ---------------- */

    public function statusBadgeLabel(string $status): string
    {
        return match ($status) {
            'pending'  => 'منتظر تایید',
            'accepted' => 'تایید شده',
            'rejected' => 'تایید نشده',
            default    => $status,
        };
    }

    public function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'accepted' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400',
            'pending'  => 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400',
            default    => 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400',
        };
    }

    public function suggestLabel(?string $suggest): string
    {
        return match ($suggest) {
            'yes'      => 'پیشنهاد می‌کنم',
            'no'       => 'پیشنهاد نمی‌کنم',
            'not_sure' => 'مطمئن نیستم',
            default    => '—',
        };
    }

    protected function adminName(): string
    {
        $admin = auth('adminPanel')->user();

        return $admin?->fullname ?? $admin?->name ?? 'مدیر';
    }

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.reviews.reviews')
            ->layout('admin-panel::layout');
    }
}
