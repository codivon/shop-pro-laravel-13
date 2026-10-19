<?php
// app/Livewire/Admin/System/ActivityLogIndex.php

namespace App\Livewire\Admin\System;

use App\Livewire\Admin\System\Concerns\FormatsActivity;
use App\Models\Admin;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;
use Spatie\Activitylog\Models\Activity;

/**
 * لاگ فعالیت‌ها — پورت ۱:۱ Back\ActivityLogController (index با همه فیلترها + آمار /
 * show به‌صورت مودال / deleteOld). فرمت‌کردن توضیحات فارسی در Concerns\FormatsActivity.
 */
#[Title('لاگ فعالیت‌ها')]
class ActivityLogIndex extends Component
{
    use WithPagination;
    use FormatsActivity;

    public int $perPage = 20;

    /* ---------------- فیلترها (State در URL — پورت request های index قدیمی) ---------------- */

    #[Url(as: 'subject_type', except: '')]
    public string $subjectType = '';

    #[Url(as: 'event', except: '')]
    public string $event = '';

    #[Url(as: 'causer_id', except: '')]
    public string $causerId = '';

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(as: 'from_date', except: '')]
    public string $fromDate = '';

    #[Url(as: 'to_date', except: '')]
    public string $toDate = '';

    /* ---------------- مودال جزئیات (پورت show قدیمی) ---------------- */

    public bool $showDetail = false;

    public ?int $viewingActivity = null;

    /* ---------------- حذف لاگ‌های قدیمی (پورت deleteOld قدیمی) ---------------- */

    public bool $confirmingDeleteOld = false;

    public int $deleteDays = 30;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('activity-log.index') ?: abort(403);
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['subjectType', 'event', 'causerId', 'search', 'fromDate', 'toDate'])) {
            $this->resetPage();
        }
    }

    /* ------------------------------------------------------------------
     | کوئری فیلترشده (پورت دقیق فیلترهای index قدیمی)
     ------------------------------------------------------------------ */
    private function filteredQuery()
    {
        $query = Activity::with(['causer', 'subject'])
            ->latest();

        // فیلتر بر اساس نوع موضوع (مقالات، محصولات و...)
        if ($this->subjectType !== '') {
            $query->where('subject_type', $this->subjectType);
        }

        // فیلتر بر اساس نوع رویداد
        if ($this->event !== '') {
            $query->where('event', $this->event);
        }

        // فیلتر بر اساس کاربر
        if ($this->causerId !== '') {
            $query->where('causer_id', $this->causerId);
        }

        // فیلتر بر اساس بازه زمانی (شمسی Y-m-d مثل قدیمی)
        if ($this->fromDate !== '') {
            $dateFrom = convertPersianToEnglish($this->fromDate);
            $dateFrom = Jalalian::fromFormat('Y-m-d', $dateFrom)->toCarbon()->startOfDay();
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($this->toDate !== '') {
            $dateTo = convertPersianToEnglish($this->toDate);
            $dateTo = Jalalian::fromFormat('Y-m-d', $dateTo)->toCarbon()->endOfDay();
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // جستجو در توضیحات
        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('description', 'like', '%'.$this->search.'%')
                    ->orWhere('log_name', 'like', '%'.$this->search.'%');
            });
        }

        return $query;
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->subjectType = '';
        $this->event = '';
        $this->causerId = '';
        $this->search = '';
        $this->fromDate = '';
        $this->toDate = '';
        $this->resetPage();
    }

    /* ---------------- لیست (پورت index — با گزارش فارسی فرمت‌شده) ---------------- */

    #[Computed]
    public function activities()
    {
        $activities = $this->filteredQuery()->paginate($this->perPage);

        // فرمت کردن لاگ‌ها برای نمایش در جدول
        $activities->getCollection()->transform(function ($activity) {
            $report = $this->formatActivityReportWithLinks($activity);
            $description = $activity->description;

            $extraDescription = null;

            if ($activity->subject_type === 'App\Models\Comment' && $description && ! in_array($description, ['created', 'updated', 'deleted', 'restored'])) {
                $extraDescription = $description;
            }

            return (object) [
                'id' => $activity->id,
                'report' => $report,
                'date' => $this->formatActivityDate($activity->created_at),
                'extra_description' => $description,
            ];
        });

        return $activities;
    }

    /* ---------------- آمار (پورت کامل بخش آمار index قدیمی) ---------------- */

    #[Computed]
    public function stats(): array
    {
        $statsQuery = $this->filteredQuery();

        // آمار پایه
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'created' => (clone $statsQuery)->where('event', 'created')->count(),
            'updated' => (clone $statsQuery)->where('event', 'updated')->count(),
            'deleted' => (clone $statsQuery)->where('event', 'deleted')->count(),
        ];

        // آمار امروز
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $stats['today'] = [
            'total' => (clone $statsQuery)->whereBetween('created_at', [$todayStart, $todayEnd])->count(),
            'created' => (clone $statsQuery)->whereBetween('created_at', [$todayStart, $todayEnd])->where('event', 'created')->count(),
            'updated' => (clone $statsQuery)->whereBetween('created_at', [$todayStart, $todayEnd])->where('event', 'updated')->count(),
            'deleted' => (clone $statsQuery)->whereBetween('created_at', [$todayStart, $todayEnd])->where('event', 'deleted')->count(),
        ];

        // آمار این هفته
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        $stats['this_week'] = [
            'total' => (clone $statsQuery)->whereBetween('created_at', [$weekStart, $weekEnd])->count(),
            'created' => (clone $statsQuery)->whereBetween('created_at', [$weekStart, $weekEnd])->where('event', 'created')->count(),
            'updated' => (clone $statsQuery)->whereBetween('created_at', [$weekStart, $weekEnd])->where('event', 'updated')->count(),
            'deleted' => (clone $statsQuery)->whereBetween('created_at', [$weekStart, $weekEnd])->where('event', 'deleted')->count(),
        ];

        // آمار این ماه
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $stats['this_month'] = [
            'total' => (clone $statsQuery)->whereBetween('created_at', [$monthStart, $monthEnd])->count(),
            'created' => (clone $statsQuery)->whereBetween('created_at', [$monthStart, $monthEnd])->where('event', 'created')->count(),
            'updated' => (clone $statsQuery)->whereBetween('created_at', [$monthStart, $monthEnd])->where('event', 'updated')->count(),
            'deleted' => (clone $statsQuery)->whereBetween('created_at', [$monthStart, $monthEnd])->where('event', 'deleted')->count(),
        ];

        // آمار بیشترین فعالیت کاربران
        $filteredForTop = clone $statsQuery;
        $filteredForTop->getQuery()->orders = [];

        $topUsers = $filteredForTop
            ->select('causer_id', 'causer_type', DB::raw('count(*) as total'))
            ->whereNotNull('causer_id')
            ->groupBy('causer_id', 'causer_type')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $causer = null;
                if ($item->causer_type === 'App\Models\Admin') {
                    $causer = Admin::find($item->causer_id);
                } elseif ($item->causer_type === 'App\Models\User') {
                    $causer = User::find($item->causer_id);
                } elseif ($item->causer_type === 'App\Models\Seller') {
                    $causer = Seller::find($item->causer_id);
                }

                return [
                    'name' => $causer->full_name ?? $causer->name ?? 'ناشناس',
                    'type' => class_basename($item->causer_type),
                    'total' => $item->total,
                ];
            });

        $stats['top_users'] = $topUsers;

        // آمار بر اساس روزهای هفته
        // DAYNAME/FIELD فقط در MySQL موجود است — روی SQLite محاسبه در PHP انجام می‌شود
        // (رفتار روی MySQL عیناً مثل قدیمی است)
        $filteredForDaily = clone $statsQuery;
        $filteredForDaily->getQuery()->orders = [];

        if (DB::connection()->getDriverName() === 'mysql') {
            $dailyStats = $filteredForDaily
                ->select(DB::raw('DAYNAME(created_at) as day'), DB::raw('count(*) as total'))
                ->groupBy('day')
                ->orderBy(DB::raw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')"))
                ->get()
                ->map(function ($item) {
                    return [
                        'day' => $this->persianDayName($item->day),
                        'total' => $item->total,
                    ];
                });
        } else {
            $rows = $filteredForDaily
                ->select('created_at')
                ->get();

            $counts = [];
            foreach ($rows as $row) {
                $day = \Carbon\Carbon::parse($row->created_at)->format('l');
                $counts[$day] = ($counts[$day] ?? 0) + 1;
            }

            $order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $dailyStats = collect($order)
                ->filter(fn ($day) => isset($counts[$day]))
                ->map(fn ($day) => ['day' => $this->persianDayName($day), 'total' => $counts[$day]])
                ->values();
        }

        $stats['daily'] = $dailyStats;

        return $stats;
    }

    private function persianDayName(string $day): string
    {
        $dayNames = [
            'Monday' => 'دوشنبه',
            'Tuesday' => 'سه‌شنبه',
            'Wednesday' => 'چهارشنبه',
            'Thursday' => 'پنج‌شنبه',
            'Friday' => 'جمعه',
            'Saturday' => 'شنبه',
            'Sunday' => 'یک‌شنبه',
        ];

        return $dayNames[$day] ?? $day;
    }

    /* ---------------- لیست‌های فیلتر (پورت subjectTypes/events/causers قدیمی) ---------------- */

    #[Computed]
    public function subjectTypes()
    {
        return Activity::select('subject_type')
            ->distinct()
            ->whereNotNull('subject_type')
            ->pluck('subject_type')
            ->map(function ($item) {
                return [
                    'value' => $item,
                    'label' => $this->getModelLabel($item),
                ];
            });
    }

    #[Computed]
    public function events()
    {
        return Activity::select('event')
            ->distinct()
            ->whereNotNull('event')
            ->pluck('event');
    }

    #[Computed]
    public function causers()
    {
        return Activity::with('causer')
            ->select('causer_id', 'causer_type')
            ->distinct()
            ->whereNotNull('causer_id')
            ->get()
            ->map(function ($item) {
                $type = '';
                $typeLabel = '';

                switch ($item->causer_type) {
                    case 'App\Models\Admin':
                        $type = 'admin';
                        $typeLabel = 'مدیر';
                        break;
                    case 'App\Models\User':
                        $type = 'user';
                        $typeLabel = 'کاربر';
                        break;
                    case 'App\Models\Seller':
                        $type = 'seller';
                        $typeLabel = 'فروشنده';
                        break;
                    default:
                        $type = 'other';
                        $typeLabel = class_basename($item->causer_type);
                }

                return [
                    'id' => $item->causer_id,
                    'name' => $item->causer ? $item->causer->full_name : 'ناشناس',
                    'type' => $type,
                    'type_label' => $typeLabel,
                    'full_display' => $item->causer ? "{$typeLabel} - {$item->causer->full_name}" : 'ناشناس',
                ];
            })
            ->unique('id')
            ->sortBy(function ($item) {
                return $item['full_display'];
            })
            ->values();
    }

    /* ---------------- مودال جزئیات (پورت show قدیمی) ---------------- */

    public function openDetail(int $id): void
    {
        auth('adminPanel')->user()?->can('activity-log.index') ?: abort(403);

        if (! Activity::find($id)) {
            $this->dispatch('toast', message: 'فعالیت یافت نشد.', type: 'error');

            return;
        }

        $this->viewingActivity = $id;
        $this->showDetail = true;
    }

    #[Computed]
    public function detailData(): ?array
    {
        if (! $this->viewingActivity) {
            return null;
        }

        $activity = Activity::with(['causer', 'subject'])->find($this->viewingActivity);

        if (! $activity) {
            return null;
        }

        // پورت eventConfig قدیمی — آیکون‌ها به آیکون‌های lucide پنل نگاشت شده‌اند
        $eventConfig = [
            'created' => ['icon' => 'plus', 'text' => 'ایجاد', 'tone' => 'green'],
            'updated' => ['icon' => 'pencil', 'text' => 'ویرایش', 'tone' => 'amber'],
            'deleted' => ['icon' => 'trash-2', 'text' => 'حذف', 'tone' => 'red'],
            'restored' => ['icon' => 'rotate-ccw', 'text' => 'بازیابی', 'tone' => 'blue'],
        ];

        $event = $eventConfig[$activity->event] ?? ['icon' => 'activity', 'text' => $activity->event, 'tone' => 'gray'];

        $actorHtml = $this->getActorLink($activity);
        $subjectHtml = $this->getSubjectLink($activity);

        $causerLink = null;
        $causerName = $activity->causer?->full_name ?? 'سیستم';
        $causerType = $this->getModelLabel($activity->causer_type);

        if ($activity->causer) {
            if ($activity->causer_type === 'App\Models\Admin') {
                $causerLink = route('admin.admins.show', $activity->causer_id);
            } elseif ($activity->causer_type === 'App\Models\User') {
                $causerLink = route('admin.users.show', $activity->causer_id);
            } elseif ($activity->causer_type === 'App\Models\Seller') {
                $causerLink = route('admin.sellers.show', $activity->causer_id);
            }
        }

        $subjectLink = null;
        $subjectName = null;
        if ($activity->subject) {
            if ($activity->subject_type === 'App\Models\Post') {
                $slug = \App\Models\Post::find($activity->subject_id)->slug ?? null;
                if ($slug) {
                    $subjectLink = route('front.articles.show', $slug);
                }
                $subjectName = $activity->subject->title ?? 'مقاله';
            } elseif ($activity->subject_type === 'App\Models\Product') {
                $subjectLink = route('front.products.show', $activity->subject_id);
                $subjectName = $activity->subject->name ?? $activity->subject->title ?? 'محصول';
            } elseif ($activity->subject_type === 'App\Models\User') {
                $subjectLink = route('admin.users.show', $activity->subject_id);
                $subjectName = $activity->subject->name ?? 'کاربر';
            } elseif ($activity->subject_type === 'App\Models\Category') {
                $subjectLink = route('admin.products.categories.index', $activity->subject_id);
                $subjectName = $activity->subject->name ?? 'دسته‌بندی';
            } elseif ($activity->subject_type === 'App\Models\Order') {
                $subjectLink = route('admin.orders.show', $activity->subject_id);
                $subjectName = 'سفارش #'.$activity->subject_id;
            }
        }

        return [
            'id' => $activity->id,
            'event' => $event['text'],
            'event_tone' => $event['tone'],
            'event_icon' => $event['icon'],
            'subject_type' => $this->getModelLabel($activity->subject_type),
            'subject_id' => $activity->subject_id,
            'subject_name' => $subjectName,
            'subject_link' => $subjectLink,
            'subject_html' => $subjectHtml,
            'causer_name' => $causerName,
            'causer_type' => $causerType,
            'causer_link' => $causerLink,
            'causer_html' => $actorHtml,
            'description' => $activity->description,
            'created_at' => jdate($activity->created_at)->format('Y/m/d - H:i'),
            'created_at_diff' => jdate($activity->created_at)->ago(),
            'properties' => $this->formatProperties($activity->properties),
        ];
    }

    /* ---------------- حذف لاگ‌های قدیمی (پورت deleteOld قدیمی) ---------------- */

    public function deleteOld(): void
    {
        auth('adminPanel')->user()?->can('activity-log.index') ?: abort(403);

        $this->validate([
            'deleteDays' => 'required|integer|min:1',
        ], [], [
            'deleteDays' => 'تعداد روز',
        ]);

        $date = now()->subDays($this->deleteDays);
        $count = Activity::where('created_at', '<', $date)->delete();

        $this->confirmingDeleteOld = false;

        unset($this->activities, $this->stats);

        $this->dispatch('toast', message: "{$count} فعالیت حذف شد", type: 'success');
    }

    /* ---------------- ابزار ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.system.activity-log')
            ->layout('admin-panel::layout');
    }
}
