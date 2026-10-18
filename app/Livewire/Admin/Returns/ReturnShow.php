<?php

namespace App\Livewire\Admin\Returns;

use App\Models\ReturnRequest;
use App\Services\AdminPanel\ReturnPersistence;
use Livewire\Attributes\Title;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

#[Title('جزئیات مرجوعی')]
class ReturnShow extends Component
{
    public ReturnRequest $returnRequest;

    /* ---------------- فرم‌های عملیات ---------------- */

    public string $adminNotes = '';

    public string $inspectionResult = '';

    public string $rejectionReason = '';

    public function mount(ReturnRequest $returnRequest): void
    {
        auth('adminPanel')->user()?->can('returns.show') ?: abort(403);

        $this->returnRequest = $returnRequest;
        $this->loadRelations();
    }

    private function loadRelations(): void
    {
        $this->returnRequest->load(['order', 'orderItem.product', 'user', 'reason', 'images']);
    }

    private function authorizeReturn(string $permission): void
    {
        auth('adminPanel')->user()?->can($permission) ?: abort(403);
    }

    /* ================================================================
     * ۱. تایید اولیه (پس از بررسی → منتظر ارسال محصول توسط مشتری)
     * ================================================================ */
    public function approve(): void
    {
        $this->authorizeReturn('returns.approve');

        $this->validate(['adminNotes' => ['nullable', 'string', 'max:500']]);

        if (! $this->returnRequest->canBeApproved()) {
            $this->dispatch('toast', message: 'این درخواست قابل تایید نیست.', type: 'error');

            return;
        }

        $this->returnRequest->update([
            'status'      => ReturnRequest::STATUS_APPROVED,
            'admin_id'    => auth('adminPanel')->id(),
            'admin_notes' => $this->adminNotes ?: null,
            'approved_at' => now(),
        ]);
        $this->returnRequest->orderItem->update(['return_status' => 'approved']);

        $this->finishAction('تایید اولیه شد. از مشتری بخواهید محصول را ارسال کند.');
    }

    /* ================================================================
     * ۲. ثبت ارسال محصول توسط مشتری (ادمین تایید می‌کند مشتری محصول را ارسال کرده)
     * ================================================================ */
    public function markCustomerShipped(): void
    {
        // ⚠ نگاشت دسترسی: اکشن سفارشی legacy عملاً بدون گیت بود (authorizeResource فقط
        // اکشن‌های RESTful را می‌پوشاند)؛ اینجا بخشی از چرخه تایید در نظر گرفته شد.
        $this->authorizeReturn('returns.approve');

        $this->validate(['adminNotes' => ['nullable', 'string', 'max:500']]);

        if (! $this->returnRequest->isApproved()) {
            $this->dispatch('toast', message: 'این درخواست در وضعیت مناسب نیست.', type: 'error');

            return;
        }

        $this->returnRequest->update([
            'status'              => ReturnRequest::STATUS_SHIPPED_BY_CUSTOMER,
            'customer_shipped_at' => now(),
            'admin_notes'         => $this->adminNotes ?: null,
        ]);
        $this->returnRequest->orderItem->update(['return_status' => 'shipped_by_customer']);

        $this->finishAction('ثبت شد. محصول توسط مشتری ارسال شده است.');
    }

    /* ================================================================
     * ۳. محصول دریافت شد — در حال بررسی نهایی
     * ================================================================ */
    public function markReceived(): void
    {
        $this->authorizeReturn('returns.received');

        $this->validate(['adminNotes' => ['nullable', 'string', 'max:500']]);

        if (! $this->returnRequest->canBeReceived()) {
            $this->dispatch('toast', message: 'این درخواست قابل دریافت نیست.', type: 'error');

            return;
        }

        $this->returnRequest->update([
            'status'      => ReturnRequest::STATUS_RECEIVED,
            'admin_notes' => $this->adminNotes ?: null,
            'received_at' => now(),
        ]);
        $this->returnRequest->orderItem->update(['return_status' => 'received']);

        $this->finishAction('محصول دریافت شد. در حال بررسی نهایی.');
    }

    /* ================================================================
     * ۴الف. محصول مشکلی نداشت → دوباره ارسال شود
     * ================================================================ */
    public function reship(): void
    {
        // ⚠ نگاشت دسترسی (انحراف مستندشده): قدیمی authorizeResource(update) استفاده می‌کرد
        // ولی «returns.update» اصلاً در درخت دسترسی وجود ندارد → عملاً بدون گیت بود.
        // نزدیک‌ترین دسترسی موجود «returns.received» انتخاب شد (عملیات انبار/دریافت کالا).
        $this->authorizeReturn('returns.received');

        $this->validate([
            'inspectionResult' => ['nullable', 'string', 'max:500'],
            'adminNotes'       => ['nullable', 'string', 'max:500'],
        ]);

        if (! $this->returnRequest->canReship()) {
            $this->dispatch('toast', message: 'این درخواست قابل این عملیات نیست.', type: 'error');

            return;
        }

        $this->returnRequest->update([
            'status'            => ReturnRequest::STATUS_RESHIPPED,
            'inspection_result' => $this->inspectionResult ?: 'محصول مشکلی نداشت',
            'admin_notes'       => $this->adminNotes ?: null,
            'reshipped_at'      => now(),
            'reship_product'    => true,
        ]);
        $this->returnRequest->orderItem->update(['return_status' => 'reshipped']);

        $this->finishAction('محصول دوباره به مشتری ارسال خواهد شد.');
    }

    /* ================================================================
     * ۴ب. محصول مشکل داشت → تایید نهایی + بازگشت وجه به کیف پول + بازگشت اعتبار
     * (منطق کامل در ReturnPersistence::complete پورت شده است)
     * ================================================================ */
    public function complete(): void
    {
        $this->authorizeReturn('returns.complete');

        $this->validate([
            'inspectionResult' => ['nullable', 'string', 'max:500'],
            'adminNotes'       => ['nullable', 'string', 'max:500'],
        ]);

        if (! $this->returnRequest->canComplete()) {
            $this->dispatch('toast', message: 'این درخواست قابل تکمیل نیست.', type: 'error');

            return;
        }

        $result = app(ReturnPersistence::class)->complete(
            $this->returnRequest,
            $this->adminNotes ?: null,
            $this->inspectionResult ?: null,
            (int) auth('adminPanel')->id()
        );

        $this->dispatch('toast', message: $result['message'], type: $result['ok'] ? 'success' : 'error');

        $this->finishAction();
    }

    /* ================================================================
     * ۵. رد درخواست — مثل قدیمی برای همه وضعیت‌ها جز completed/cancelled
     * (legacy نشان رد در فرم‌های pending و received هم داشت)
     * ================================================================ */
    public function reject(): void
    {
        $this->authorizeReturn('returns.reject');

        $this->validate(['rejectionReason' => ['required', 'string', 'max:500']]);

        if (in_array($this->returnRequest->status, ['completed', 'cancelled'])) {
            $this->dispatch('toast', message: 'این درخواست قابل رد نیست.', type: 'error');

            return;
        }

        $this->returnRequest->update([
            'status'           => ReturnRequest::STATUS_REJECTED,
            'admin_id'         => auth('adminPanel')->id(),
            'rejection_reason' => $this->rejectionReason,
            'rejected_at'      => now(),
        ]);
        $this->returnRequest->orderItem->update(['return_status' => 'rejected']);

        $this->finishAction('درخواست مرجوعی رد شد.');
    }

    /** ریست فرم‌ها + رفرش رکورد تا UI بدون رفرش صفحه به‌روز شود */
    private function finishAction(?string $message = null): void
    {
        $this->reset(['adminNotes', 'inspectionResult', 'rejectionReason']);
        $this->returnRequest->refresh();
        $this->loadRelations();

        if ($message !== null) {
            $this->dispatch('toast', message: $message, type: 'success');
        }
    }

    /* ---------------- هلپرهای نمایش ---------------- */

    /** تایم‌لاین وضعیت — هر زمانی که ثبت شده باشد نمایش داده می‌شود */
    public function timeline(): array
    {
        $r = $this->returnRequest;

        $steps = [
            ['ثبت درخواست', $r->created_at, 'clock'],
            ['تایید اولیه', $r->approved_at, 'check-circle'],
            ['ارسال توسط مشتری', $r->customer_shipped_at, 'truck'],
            ['دریافت محصول', $r->received_at, 'box'],
            ['ارسال مجدد به مشتری', $r->reshipped_at, 'truck'],
            ['تایید نهایی و تسویه', $r->completed_at, 'check-check'],
            ['رد درخواست', $r->rejected_at, 'circle-x'],
        ];

        $timeline = [];

        foreach ($steps as [$label, $date, $icon]) {
            if ($date) {
                $timeline[] = [
                    'label' => $label,
                    'date'  => $this->jdate($date, 'Y/m/d - H:i'),
                    'icon'  => $icon,
                ];
            }
        }

        return $timeline;
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
        return view('admin-panel::livewire.admin.returns.show')
            ->layout('admin-panel::layout');
    }
}
