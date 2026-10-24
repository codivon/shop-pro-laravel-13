<?php

namespace App\Livewire\Admin\System;

use App\Services\PulseService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

/**
 * مانیتور سیستم — پورت Back\PulseController + PulseService->getDashboardData()
 *
 * ★ SSE قدیمی (stream با sleep(10)) جایگزین شد با wire:poll.10s —
 *   همان رفتار به‌روزرسانی هر ۱۰ ثانیه، بدون نگه‌داشتن اتصال PHP-FPM (طبق spec).
 * ★ دانلود گزارش درخواست‌های کند و کوئری‌های کند (فاز ۲۲) — فایل متنی ساخت‌یافته برای ارسال به پشتیبانی/ایجنت.
 */
#[Title('مانیتور سیستم')]
class PulseIndex extends Component
{
    public array $pulse = [];

    public array $slowRequests = [];

    public array $slowQueries = [];

    public array $exceptions = [];

    public array $queueJobs = [];

    public ?string $lastUpdate = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('pulse.monitor') ?: abort(403);

        $this->loadData();
    }

    /* ---------------- بارگذاری دیتا (پورت index قدیمی) ---------------- */

    public function loadData(): void
    {
        $data = app(PulseService::class)->getDashboardData();

        $this->pulse = $data['pulse'];
        $this->slowRequests = array_slice($data['slow_requests'], 0, 20);
        $this->slowQueries = array_slice($data['slow_queries'], 0, 20);
        $this->exceptions = array_slice($data['exceptions'], 0, 20);
        $this->queueJobs = array_slice($data['queue_jobs'], 0, 20);
        $this->lastUpdate = now()->format('H:i:s');
    }

    /* ---------------- رفرش فوری (پورت refresh قدیمی: clearCache + reload) ---------------- */

    public function refreshData(): void
    {
        auth('adminPanel')->user()?->can('pulse.monitor') ?: abort(403);

        app(PulseService::class)->clearCache();
        $this->loadData();
    }

    /** poll هر ۱۰ ثانیه (جایگزین SSE قدیمی) */
    public function pollData(): void
    {
        $this->loadData();
    }

    /* ---------------- دانلود گزارش‌ها (فاز ۲۲) ---------------- */

    /**
     * دانلود گزارش کوئری‌های کند — فایل متنی ساخت‌یافته (قابل ارسال به ایجنت/پشتیبانی)
     */
    public function downloadSlowQueries()
    {
        auth('adminPanel')->user()?->can('pulse.monitor') ?: abort(403);

        $rows = app(PulseService::class)->exportSlowQueries(100);

        $report = $this->buildReportHeader('کوئری‌های کند', count($rows));

        foreach ($rows as $i => $row) {
            $report .= "\n" . str_repeat('=', 72) . "\n";
            $report .= '#  ' . ($i + 1) . "\n";
            $report .= 'مدت   : ' . number_format($row['duration']) . " ms\n";
            $report .= 'زمان  : ' . $row['time'] . "  (" . $row['time_ago'] . ")\n";
            if (!empty($row['source'])) {
                $report .= 'منبع  : ' . $row['source'] . "\n";
            }
            $report .= "SQL   :\n" . $row['sql'] . "\n";
        }

        return $this->streamReport($report, 'slow-queries');
    }

    /**
     * دانلود گزارش درخواست‌های کند — فایل متنی ساخت‌یافته
     */
    public function downloadSlowRequests()
    {
        auth('adminPanel')->user()?->can('pulse.monitor') ?: abort(403);

        $rows = app(PulseService::class)->exportSlowRequests(100);

        $report = $this->buildReportHeader('درخواست‌های کند', count($rows));

        foreach ($rows as $i => $row) {
            $report .= "\n" . str_repeat('=', 72) . "\n";
            $report .= '#  ' . ($i + 1) . "\n";
            $report .= 'مدت   : ' . number_format($row['duration']) . " ms\n";
            $report .= 'زمان  : ' . $row['time'] . "  (" . $row['time_ago'] . ")\n";
            $report .= 'متد   : ' . $row['method'] . "\n";
            $report .= 'URI   : ' . $row['uri'] . "\n";
            if (!empty($row['via'])) {
                $report .= 'منبع  : ' . $row['via'] . "\n";
            }
        }

        return $this->streamReport($report, 'slow-requests');
    }

    protected function buildReportHeader(string $section, int $count): string
    {
        $now = now();
        $j   = Jalalian::fromDateTime($now);

        $line = str_repeat('=', 72);

        return <<<TEXT
        {$line}
        گزارش {$section} — مانیتور سیستم
        {$line}
        تولید شده   : {$j->format('Y/m/d H:i:s')} ({$now->format('Y-m-d H:i:s')})
        بازه گزارش  : 24 ساعت گذشته
        تعداد موارد : {$count}
        TEXT;
    }

    protected function streamReport(string $report, string $slug)
    {
        $filename = $slug . '-' . now()->format('Ymd-His') . '.txt';

        return response()->streamDownload(
            function () use ($report) {
                echo $report;
            },
            $filename,
            ['Content-Type' => 'text/plain; charset=UTF-8']
        );
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.system.pulse')
            ->layout('admin-panel::layout');
    }
}
