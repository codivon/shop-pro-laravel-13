<?php

namespace App\Services\AdminPanel;

use App\Models\ReturnRequest;
use App\Models\WalletHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * پورت ۱:۱ متد complete() کنترلر قدیمی Back/ReturnController.php (خط ۱۷۲ تا ۳۵۶)
 *
 * هسته اصلی مرجوعی:
 * - سفارش نقدی: کل مبلغ آیتم به کیف پول برمی‌گردد
 * - سفارش اعتباری: مبلغ پرداخت‌شده (قسط اول + اقساط پرداخت‌شده) × سهم آیتم به کیف پول
 *   و اعتبار استفاده‌شده برای آیتم به حساب اعتباری کاربر برمی‌گردد
 * - سفارش اقساطی: مبلغ پرداخت‌شده (پیش‌پرداخت + اقساط پرداخت‌شده) × سهم آیتم به کیف پول
 *
 * نکته: هزینه ارسال هرگز جزو مرجوعی محاسبه نمی‌شود.
 *
 * باگ‌های قدیمی اصلاح‌شده:
 * 1. auth()->id() → auth('adminPanel')->id() (ابهام گارد؛ ادمین‌ها روی گارد adminPanel هستند)
 * 2. متغیر تعریف‌نشده $refundToWallet در scope بستار قدیمی (روی request بود) → پارامتر صریح true
 *    (چک‌باکس قدیمی همیشه تیک‌خورده بود؛ مقدار پیش‌فرض legacy هم true بود)
 * 3. catch (\Exception) → catch (\Throwable) تا خطاهای Error هم تراکنش را به‌درستی برگردانند
 */
class ReturnPersistence
{
    /**
     * تایید نهایی مرجوعی + بازگشت وجه به کیف پول + بازگشت اعتبار + لغو طرح اقساطی
     *
     * @return array{ok: bool, message: string}
     */
    public function complete(ReturnRequest $returnRequest, ?string $adminNotes, ?string $inspectionResult, int $adminId): array
    {
        if (! $returnRequest->canComplete()) {
            return ['ok' => false, 'message' => 'این درخواست قابل تکمیل نیست.'];
        }

        // ===== محاسبه مجدد breakdown (برای اطمینان از مقادیر به‌روز) =====
        // اگر کاربر بین ثبت درخواست و تکمیل، قسط جدیدی پرداخت کرده باشد محاسبات به‌روز است.
        $breakdown = $returnRequest->calculateRefundBreakdown();

        $refundAmount        = $breakdown['refund_amount'];
        $walletRefundAmount  = $breakdown['wallet_refund_amount'];
        $creditRestoreAmount = $breakdown['credit_restore_amount'];
        $refundToWallet      = true;

        try {
            DB::transaction(function () use (
                $returnRequest, $adminNotes, $inspectionResult, $adminId,
                $refundAmount, $walletRefundAmount, $creditRestoreAmount, $refundToWallet
            ) {
                // ===== ۱. بازگشت وجه به کیف پول کاربر =====
                if ($refundToWallet && $walletRefundAmount > 0 && ! $returnRequest->paid_to_wallet) {
                    $user   = $returnRequest->user;
                    $wallet = $user ? $user->getWallet() : null;

                    if ($wallet) {
                        $wallet->increment('balance', $walletRefundAmount);

                        WalletHistory::create([
                            'wallet_id'   => $wallet->id,
                            'type'        => 'deposit',
                            'amount'      => $walletRefundAmount,
                            'source'      => 'admin',
                            'status'      => 'success',
                            'order_id'    => $returnRequest->order_id,
                            'description' => sprintf(
                                "بازگشت وجه مرجوعی #%d — سفارش #%d — محصول: %s — مبلغ: %s ت",
                                $returnRequest->id,
                                $returnRequest->order_id,
                                $returnRequest->orderItem?->title ?? '—',
                                number_format($walletRefundAmount)
                            ),
                        ]);
                    } else {
                        Log::warning('ReturnRequest: Wallet not found for user', [
                            'user_id' => $returnRequest->user_id,
                        ]);
                    }
                }

                // ===== ۲. بازگشت اعتبار به حساب اعتباری کاربر (فقط سفارشات اعتباری) =====
                if ($creditRestoreAmount > 0 && ! $returnRequest->credit_restored) {
                    $this->restoreCredit($returnRequest, $creditRestoreAmount, $adminId);
                }

                // ===== ۳. لغو طرح اعتباری/اقساطی =====
                // جلوگیری از پرداخت اقساط باقی‌مانده برای محصول مرجوع‌شده
                $returnRequest->cancelPaymentPlan();

                // ===== ۴. آپدیت درخواست مرجوعی =====
                $returnRequest->update([
                    'status'                => ReturnRequest::STATUS_COMPLETED,
                    'admin_id'              => $adminId,
                    'admin_notes'           => $adminNotes,
                    'inspection_result'     => $inspectionResult ?: 'محصول مشکل داشت',
                    'completed_at'          => now(),
                    'refund_to_wallet'      => $refundToWallet,
                    'refund_amount'         => $refundAmount,
                    'wallet_refund_amount'  => $walletRefundAmount,
                    'credit_restore_amount' => $creditRestoreAmount,
                    'paid_to_wallet'        => $refundToWallet && $walletRefundAmount > 0,
                    'credit_restored'       => $creditRestoreAmount > 0,
                ]);

                // ===== ۵. آپدیت آیتم سفارش =====
                $returnRequest->orderItem->update([
                    'return_status'   => 'completed',
                    'refunded'        => true,
                    'refunded_at'     => now(),
                    'refunded_amount' => $refundAmount,
                ]);
            });

            // ===== پیام موفقیت با جزئیات (پورت کامل پیام قدیمی) =====
            $successMsg = 'مرجوعی تایید نهایی شد.';

            if ($walletRefundAmount > 0) {
                $successMsg .= ' ' . number_format($walletRefundAmount) . ' تومان به کیف پول کاربر واریز شد.';
            }

            if ($creditRestoreAmount > 0) {
                $successMsg .= ' ' . number_format($creditRestoreAmount) . ' تومان به اعتبار کاربر بازگشت داده شد.';
            }

            if ($walletRefundAmount === 0 && $creditRestoreAmount === 0) {
                $successMsg .= ' هیچ مبلغی برای بازگشت وجود نداشت.';
            }

            // اگر طرح اعتباری/اقساطی لغو شده
            if ($returnRequest->isCreditPayment() || $returnRequest->isInstallmentPayment()) {
                $successMsg .= ' طرح ' . $returnRequest->paymentTypeLabel() . ' لغو شد و اقساط باقی‌مانده باطل شدند.';
            }

            return ['ok' => true, 'message' => $successMsg];

        } catch (\Throwable $e) {
            Log::error('ReturnRequest: Error completing refund', [
                'return_request_id' => $returnRequest->id,
                'admin_id'          => $adminId,
                'error'             => $e->getMessage(),
                'trace'             => $e->getTraceAsString(),
            ]);

            return ['ok' => false, 'message' => 'خطا در تکمیل مرجوعی: ' . $e->getMessage()];
        }
    }

    /**
     * بازگشت اعتبار به حساب اعتباری کاربر (CreditPay)
     * پورت ۱:۱ متد private restoreCredit() قدیمی
     */
    private function restoreCredit(ReturnRequest $returnRequest, int $amount, int $adminId): void
    {
        if (! function_exists('module_is_active') || ! module_is_active('CreditPay')) {
            Log::warning('ReturnRequest: CreditPay module not active, cannot restore credit', [
                'return_request_id' => $returnRequest->id,
            ]);

            return;
        }

        try {
            $creditOrder = \Modules\CreditPay\Models\CreditOrder::where('order_id', $returnRequest->order_id)->first();

            if (! $creditOrder) {
                Log::warning('ReturnRequest: CreditOrder not found for credit restore', [
                    'order_id' => $returnRequest->order_id,
                ]);

                return;
            }

            $account = $creditOrder->account;

            if (! $account) {
                Log::warning('ReturnRequest: CreditAccount not found', [
                    'credit_order_id' => $creditOrder->id,
                ]);

                return;
            }

            // کاهش used_credit و افزایش available_credit
            $account->decrement('used_credit', $amount);
            $account->increment('available_credit', $amount);
            $account->save();

            // ثبت تراکنش
            \Modules\CreditPay\Models\CreditTransaction::create([
                'account_id'     => $account->id,
                'type'           => 'refund_increase',
                'amount'         => $amount,
                'balance_after'  => $account->fresh()->available_credit,
                'description'    => sprintf(
                    'بازگشت اعتبار مرجوعی #%d — سفارش #%d — محصول: %s',
                    $returnRequest->id,
                    $returnRequest->order_id,
                    $returnRequest->orderItem?->title ?? '—'
                ),
                'order_id'       => $returnRequest->order_id,
                'reference_type' => ReturnRequest::class,
                'reference_id'   => $returnRequest->id,
                'created_by'     => $adminId,
            ]);

            Log::info('ReturnRequest: Credit restored successfully', [
                'return_request_id' => $returnRequest->id,
                'account_id'        => $account->id,
                'amount'            => $amount,
            ]);

        } catch (\Exception $e) {
            Log::error('ReturnRequest: Error restoring credit', [
                'return_request_id' => $returnRequest->id,
                'error'             => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
