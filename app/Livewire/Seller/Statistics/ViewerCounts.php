<?php

namespace App\Livewire\Seller\Statistics;

use App\Models\Viewer;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

/**
 * فاز ۱۰۸ — صفحهٔ «بازدیدکنندگان یکتا» پنل مدرن (پورت SellerStatisticsController@viewerCounts).
 */
#[Title('بازدیدکنندگان یکتا')]
#[Layout('sellers.layouts.panel')]
class ViewerCounts extends Component
{
    #[Url(as: 'period')]
    public string $period = 'weekly';

    public array $chartLabels = [];

    public array $chartData = [];

    public string $total = '0';

    public string $avg = '0';

    public function mount(): void
    {
        if (! in_array($this->period, ['weekly', 'monthly', 'yearly'], true)) {
            $this->period = 'weekly';
        }

        $sellerSlug = seller()->slug;

        if (! $sellerSlug) {
            $this->redirect(route('seller.dashboard'), navigate: true);

            return;
        }

        [$labels, $data, $total, $avg] = $this->compute($sellerSlug);

        $this->chartLabels = $labels;
        $this->chartData = $data;
        $this->total = formatPriceUnits($total);
        $this->avg = formatPriceUnits($avg);
    }

    /** شمارش بازدیدکنندهٔ یکتا (DISTINCT ip) با همان کوئری پایهٔ سه‌فیلدی */
    private function compute(string $sellerSlug): array
    {
        $now = now();
        $start = match ($this->period) {
            'yearly' => $now->copy()->startOfYear()->subYears(4),
            'monthly' => $now->copy()->subMonths(11)->startOfMonth(),
            default => $now->copy()->subDays(6)->startOfDay(),
        };

        $base = Viewer::where(function ($query) use ($sellerSlug) {
            $query->where('path', 'like', '/store/'.$sellerSlug.'%')
                ->orWhere('product_path', 'like', $sellerSlug.'%')
                ->orWhere('page_path', 'like', '%/store/%')
                ->orWhere('page_path', 'like', '%/shop%');
        })->whereBetween('created_at', [$start, $now]);

        $rows = match ($this->period) {
            'yearly' => (clone $base)
                ->selectRaw('YEAR(created_at) as bucket, COUNT(DISTINCT ip) as total')
                ->groupBy('bucket')->orderBy('bucket')->get(),
            'monthly' => (clone $base)
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as bucket, COUNT(DISTINCT ip) as total')
                ->groupBy('bucket')->orderBy('bucket')->get(),
            default => (clone $base)
                ->selectRaw('DATE(created_at) as bucket, COUNT(DISTINCT ip) as total')
                ->groupBy('bucket')->orderBy('bucket')->get(),
        };

        $labels = $rows->map(function ($row) {
            return match ($this->period) {
                'yearly' => (string) $row->bucket,
                'monthly' => Jalalian::fromCarbon(Carbon::parse($row->bucket.'-01'))->format('%B %Y'),
                default => Jalalian::fromCarbon(Carbon::parse($row->bucket))->format('%m/%d'),
            };
        })->values()->all();

        $data = $rows->map(fn ($row) => (int) $row->total)->values()->all();
        $total = array_sum($data);
        $avg = count($data) > 0 ? round($total / count($data), 1) : 0;

        return [$labels, $data, $total, $avg];
    }

    public function render()
    {
        return view('sellers.panel.statistics.counts', ['typeLabel' => 'بازدیدکنندهٔ یکتا']);
    }
}
