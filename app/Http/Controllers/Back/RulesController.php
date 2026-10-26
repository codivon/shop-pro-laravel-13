<?php

namespace Modules\CustomerClub\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CustomerClub\Models\LoyaltyRule;

class RulesController extends Controller
{
    public function index()
    {
        return view('customer-club::back.rules.index', [
            'rules' => LoyaltyRule::query()->orderBy('id')->get(),
        ]);
    }

    /**
     * ویرایش قانون — شامل کلید فعال/غیرفعال همان قانون (الزام شخصی‌سازی).
     */
    public function update(Request $request, LoyaltyRule $rule)
    {
        $data = $request->validate([
            'is_active'   => ['required', 'boolean'],
            'points'      => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'amount_step' => ['nullable', 'integer', 'min:1000', 'max:1000000000'],
            'title'       => ['required', 'string', 'max:120'],
        ]);

        $rule->update([
            'is_active'   => (bool) $data['is_active'],
            'title'       => $data['title'],
            'points'      => $data['points'] ?? $rule->points,
            'amount_step' => $data['amount_step'] ?? $rule->amount_step,
        ]);

        session()->put('toast-success', "قانون «{$rule->title}» ذخیره شد.");

        return redirect()->route('admin.customerclub.rules.index');
    }
}
