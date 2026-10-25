<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\PriceAlert;

/**
 * فاز ۷۸ — مدیریت هشدارهای کاهش قیمت (پنل قدیم).
 * پورت پنل جدید: App\Livewire\Admin\PriceAlert\PriceAlertIndex
 */
class PriceAlertController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:products.price-alert');
    }

    public function index()
    {
        $priceAlerts = PriceAlert::query()
            ->with('product')
            ->latest()
            ->paginate(15);

        return view('back.price-alerts.index', compact('priceAlerts'));
    }

    public function destroy(PriceAlert $priceAlert)
    {
        $priceAlert->delete();

        return response('success');
    }
}
