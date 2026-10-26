<?php

namespace Modules\CustomerClub\Services;

use App\Models\User;
use App\Models\WalletHistory;
use Illuminate\Support\Facades\DB;
use Modules\CustomerClub\Models\ClubSetting;
use Modules\CustomerClub\Models\GiftCard;

/**
 * سرویس کارت هدیه.
 * --------------------------------------------------------------------------
 * صدور دسته‌ای (ادمین) با کدهای CC-XXXX-XXXX + فعال‌سازی مشتری با شارژ
 * مستقیم کیف پول هسته. شارژ داخل transaction با lockForUpdate انجام می‌شود
 * (ضد مصرف دوباره در رقابت همزمان) و همهٔ شارژها WalletHistory هسته دارند
 * (audit یکپارچه با بقیهٔ فروشگاه).
 */
class GiftCardService
{
    /** آیا کارت هدیه از تنظیمات روشن است؟ */
    public static function enabled(): bool
    {
        return ClubSetting::enabled('club_enabled')
            && ClubSetting::enabled('giftcards_enabled');
    }

    /**
     * صدور دسته‌ای کارت هدیه.
     *
     * @param  int                     $count      تعداد (تا سقف ۵۰۰)
     * @param  int                     $amount     مبلغ هر کارت (تومان)
     * @param  ?\Carbon\CarbonInterface $expiresAt انقضای کارت‌ها (nullable = بی‌انقضا)
     * @param  string                  $note       یادداشت ادمین
     * @return array{0: \Illuminate\Support\Collection, 1: string} [کارت‌ها, شناسهٔ دسته]
     */
    public static function issue(int $count, int $amount, ?\Carbon\CarbonInterface $expiresAt = null, string $note = ''): array
    {
        $count   = max(1, min(500, $count));
        $batchId = 'B-'.now()->format('ymdHis').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        $cards = collect();

        for ($i = 0; $i < $count; $i++) {
            $cards->push(GiftCard::query()->create([
                'code'       => GiftCard::generateCode(),
                'batch_id'   => $batchId,
                'amount'     => $amount,
                'status'     => GiftCard::STATUS_ACTIVE,
                'expires_at' => $expiresAt,
                'note'       => $note,
            ]));
        }

        return [$cards, $batchId];
    }

    /**
     * فعال‌سازی کارت هدیه توسط مشتری → شارژ کیف پول هسته.
     *
     * @param  User   $user
     * @param  string $code
     * @return array{0: bool, 1: string, 2: ?WalletHistory}
     */
    public static function redeem(User $user, string $code): array
    {
        if (!self::enabled()) {
            return [false, 'کارت هدیه در حال حاضر غیرفعال است.', null];
        }

        $code = trim(strtoupper($code));

        if ($code === '') {
            return [false, 'کد کارت هدیه را وارد کنید.', null];
        }

        return DB::transaction(function () use ($user, $code) {
            /** @var ?GiftCard $card */
            $card = GiftCard::query()->where('code', $code)->lockForUpdate()->first();

            if (!$card) {
                return [false, 'کارت هدیه‌ای با این کد پیدا نشد.', null];
            }

            if ($blocker = $card->redeemBlocker()) {
                return [false, $blocker, null];
            }

            $wallet = $user->getWallet();

            $wallet->increment('balance', $card->amount);

            $history = WalletHistory::query()->create([
                'wallet_id'   => $wallet->id,
                'type'        => 'deposit',
                'amount'      => (int) $card->amount,
                'source'      => 'user',
                'status'      => 'success',
                'description' => "باشگاه مشتریان — فعال‌سازی کارت هدیه {$card->code}",
            ]);

            $card->update([
                'status'  => GiftCard::STATUS_REDEEMED,
                'used_by' => $user->id,
                'used_at' => now(),
            ]);

            return [true, 'کارت هدیه با موفقیت فعال شد و '.number_format($card->amount).' تومان به کیف پول شما اضافه شد.', $history];
        });
    }
}
