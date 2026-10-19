<?php

namespace App\Livewire\Admin\System;

use App\Services\PulseService;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * مانیتور سیستم — پورت Back\PulseController + PulseService->getDashboardData()
 *
 * ★ SSE قدیمی (stream با sleep(10)) جایگزین شد با wire:poll.10s —
 *   همان رفتار به‌روزرسانی هر ۱۰ ثانیه، بدون نگه‌داشتن اتصال PHP-FPM (طبق spec).
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

    public function render()
    {
        return view('admin-panel::livewire.admin.system.pulse')
            ->layout('admin-panel::layout');
    }
}
