{{-- بازدیدهای کاربر — پنل جدید (فاز ۴) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                تعداد کل: <span class="fa-digits">{{ number_format($this->views->total()) }}</span>
            </span>
            <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                کاربر: {{ $user->fullname ?? '#' . $user->id }}
            </span>
        </div>
        <a href="{{ route('np.users.show', $user) }}" wire:navigate class="ap-btn-outline !py-2">
            <x-admin-panel::icon name="arrow-right" class="size-4" />
            پرونده کاربر
        </a>
    </div>

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-3xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-14 px-3 py-3 text-start font-bold">#</th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ</th>
                        <th class="px-3 py-3 text-start font-bold">IP</th>
                        <th class="px-3 py-3 text-start font-bold">پلتفرم</th>
                        <th class="px-3 py-3 text-start font-bold">آدرس</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->views as $view)
                        <tr wire:key="uv-{{ $view->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 text-ink-3">{{ $loop->iteration + ($this->views->firstItem() ?? 1) - 1 }}</td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($view->created_at) }}</td>
                            <td class="px-3 py-3 font-mono text-xs text-ink-2 dark:text-night-ink-2">{{ $view->ip }}</td>
                            <td class="px-3 py-3">{{ \get_option_property($view->options, 'platform') ?: '—' }}</td>
                            <td class="max-w-72 px-3 py-3" dir="ltr">
                                <a href="{{ url(urldecode($view->path)) }}" target="_blank" rel="noopener"
                                   class="truncate text-brand-600 hover:underline dark:text-brand-400">
                                    {{ \Illuminate\Support\Str::limit(urldecode($view->path), 70) }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="eye" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">هنوز بازدیدی ثبت نشده است</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- صفحه‌بندی --}}
        <div class="border-t border-line px-4 py-3 dark:border-night-line">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-ink-3 dark:text-night-ink-2">
                    نمایش <span class="fa-digits">{{ number_format($this->views->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->views->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->views->total()) }}</span> بازدید
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->views->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->views->onEachSide(1)->toArray()['links'] as $link)
                        @if ($link['label'] === '...')
                            <span class="px-2 text-xs text-ink-3">…</span>
                        @elseif (! is_numeric(trim($link['label'])))
                            {{-- دکمه‌های قبلی/بعدی بالاتر مدیریت شده‌اند --}}
                        @elseif ($link['active'])
                            <span class="fa-digits flex size-8 items-center justify-center rounded-lg bg-brand-600 text-[13px] font-bold text-white">{{ $link['label'] }}</span>
                        @else
                            <button wire:click="gotoPage({{ trim($link['label']) }})"
                                    class="fa-digits flex size-8 items-center justify-center rounded-lg text-[13px] font-semibold text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3">
                                {{ $link['label'] }}
                            </button>
                        @endif
                    @endforeach

                    @if ($this->views->hasMorePages())
                        <button wire:click="nextPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه بعد">
                            <x-admin-panel::icon name="chevron-left" class="size-4" />
                        </button>
                    @else
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-left" class="size-4" /></span>
                    @endif
                </nav>
            </div>
        </div>
    </div>
</div>
