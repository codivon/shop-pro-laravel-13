<?php

namespace Modules\CustomerClub\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CustomerClub\Models\ClubSetting;
use Modules\CustomerClub\Models\GiftCard;
use Modules\CustomerClub\Services\GiftCardService;

class GiftCardController extends Controller
{
    public function index(Request $request)
    {
        $query = GiftCard::query()->with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('code')) {
            $query->where('code', 'like', '%'.strtoupper(trim($request->code)).'%');
        }

        $stats = [
            'total'    => GiftCard::query()->count(),
            'active'   => GiftCard::query()->where('status', GiftCard::STATUS_ACTIVE)->count(),
            'redeemed' => GiftCard::query()->where('status', GiftCard::STATUS_REDEEMED)->count(),
            'disabled' => GiftCard::query()->where('status', GiftCard::STATUS_DISABLED)->count(),
        ];

        return view('customer-club::back.giftcards.index', [
            'rows'    => $query->paginate(30)->withQueryString(),
            'stats'   => $stats,
            'feature' => ClubSetting::enabled('giftcards_enabled'),
            'filterStatus' => $request->status,
            'filterCode'   => $request->code,
        ]);
    }

    /**
     * صدور دسته‌ای کارت هدیه — خروجی: لینک CSV برای دریافت.
     */
    public function issue(Request $request)
    {
        $data = $request->validate([
            'count'      => ['required', 'integer', 'min:1', 'max:500'],
            'amount'     => ['required', 'integer', 'min:1000', 'max:1000000000'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'note'       => ['nullable', 'string', 'max:200'],
        ]);

        [$cards, $batchId] = GiftCardService::issue(
            (int) $data['count'],
            (int) $data['amount'],
            isset($data['expires_at']) ? \Illuminate\Support\Carbon::parse($data['expires_at'])->endOfDay() : null,
            $data['note'] ?? ''
        );

        $csv = "code,amount,expires_at\n";
        foreach ($cards as $card) {
            $csv .= "{$card->code},{$card->amount},{$card->expires_at}\n";
        }

        $file = 'gift-cards-'.now()->format('YmdHis').'.csv';
        \Illuminate\Support\Facades\Storage::disk('local')->put('giftcards/'.$file, $csv);

        session()->put('toast-success', "{$cards->count()} کارت هدیه صادر شد (دستهٔ {$batchId}).");

        return redirect()
            ->route('admin.customerclub.giftcards.index')
            ->with('giftcards_csv', $file);
    }

    /** فعال/غیرفعال‌سازی کارت (فقط کارتِ مصرف‌نشده) */
    public function toggle(GiftCard $giftCard)
    {
        if ($giftCard->status === GiftCard::STATUS_REDEEMED) {
            session()->put('toast-error', 'کارت مصرف‌شده قابل تغییر نیست.');

            return redirect()->route('admin.customerclub.giftcards.index');
        }

        $giftCard->update([
            'status' => $giftCard->status === GiftCard::STATUS_ACTIVE
                ? GiftCard::STATUS_DISABLED
                : GiftCard::STATUS_ACTIVE,
        ]);

        session()->put('toast-success', 'وضعیت کارت تغییر کرد.');

        return redirect()->route('admin.customerclub.giftcards.index');
    }
}
