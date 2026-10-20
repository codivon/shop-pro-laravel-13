{{-- یک آیتم منو (برگ) — $item از والد، $menuParamKeys از sidebar (فاز ۱۹)، $menuExactRoutes از sidebar (فاز ۲۱) --}}
@php
    $menuExactRoutes = $menuExactRoutes ?? [];
    $url = '#';
    $isActive = false;
    if (!empty($item['route'])) {
        try {
            $url = route($item['route'], $item['params'] ?? []);
            if (str_starts_with($item['route'], 'np.')) {
                $routeName = request()->route()?->getName();

                /*
                | فاز ۱۹ — فعال‌سازی هوشمند منو:
                | ۱) آیتم‌های با params (مثل «سفارشات پرداخت شده») فقط وقتی فعال‌اند که
                |    پارامترهایشان در URL جاری عیناً باشد → دیگر سه آیتم هم‌زمان فعال نمی‌شوند.
                | ۲) آیتم بدون پارامتر، وقتی هم‌خانواده‌هایش با پارامتر تعریف شده‌اند فقط
                |    وقتی فعال است که هیچ‌کدام از آن کلیدها در URL نباشد («همه سفارشات»).
                | ۳) صفحات زیرمجموعه (edit/show/movements/…) هم خانواده‌ی منو را فعال می‌کنند
                |    (مثل تاریخچه حرکات انبار → «لیست انبارها» فعال و گروه باز می‌ماند).
                */
                $activeQuery = $item['active_query'] ?? ($item['params'] ?? []);

                $queryMatches = function () use ($activeQuery) {
                    return collect($activeQuery)->every(fn ($v, $k) => request()->query($k) == $v);
                };
                $noConflict = function () use ($item, $menuParamKeys) {
                    $keys = $menuParamKeys[$item['route']] ?? [];
                    return collect($keys)->every(fn ($k) => request()->query($k) === null);
                };

                if (! empty($item['active_routes']) && is_array($item['active_routes'])) {
                    // استثنا: آیتم‌هایی که خودشان صریحاً الگوی فعال‌سازی داده‌اند (مثل ابزارک‌ها)
                    $isActive = request()->routeIs($item['active_routes']);
                } elseif ($routeName === $item['route']) {
                    $isActive = $activeQuery ? $queryMatches() : $noConflict();
                } else {
                    /*
                    | خانواده‌ی روت — فقط برای آیتم‌های «لیست» (*.index): صفحات زیرمجموعه
                    | (show/edit/movements/…) آیتم لیستِ خانواده را فعال می‌کنند
                    | (مثل تاریخچه حرکات انبار → «لیست انبارها» فعال و گروه باز می‌ماند).
                    | آیتم‌های غیر index (کنسل شده/ثبت جدید/…) بدون روت دقیق فعال نمی‌شوند.
                    */
                    $family = preg_replace('/\.[^.]+$/', '', $item['route']);
                    if (
                        str_ends_with($item['route'], '.index')
                        && $routeName && str_starts_with($routeName, $family . '.')
                        /* فاز ۲۱ — اگر صفحه‌ی جاری آیتم اختصاصی در منو دارد (مثل np.products.create)،
                           آیتم «لیست» خانواده روشن نشود؛ قاعده‌ی خانواده فقط برای صفحات بی‌آیتم (edit/show/…). */
                        && ! in_array($routeName, $menuExactRoutes)
                    ) {
                        $isActive = $activeQuery ? $queryMatches() : $noConflict();
                    }
                }
            }
        } catch (\Throwable $e) {
            $url = '#';
        }
    }
@endphp

<a href="{{ $url }}"
   @if (($item['external'] ?? false)) target="_blank" rel="noopener" @else wire:navigate @endif
   class="ap-menu-link group/item {{ $isActive ? 'is-active' : '' }}"
   @if ($isActive) aria-current="page" @endif>
    <x-admin-panel::icon :name="$item['icon'] ?? 'circle'" class="size-4 shrink-0 opacity-70 transition-opacity group-hover/item:opacity-100" />
    <span class="truncate">{{ $item['label'] }}</span>
    @if ($isActive)
        <span class="ms-auto size-1.5 rounded-full bg-brand-500"></span>
    @endif
</a>
