{{-- فرم ایجاد/ویرایش نوشته — پنل جدید (فاز ۶) | پورت back/posts/create + back/posts/edit --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl">
    <form wire:submit="save" class="space-y-6"
          x-on:keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); }">

        {{-- هدر --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <x-admin-panel::icon name="file-text" class="size-5" />
                </span>
                <div>
                    <h2 class="text-base font-extrabold">
                        {{ $isEditing ? 'ویرایش نوشته: ' . ($title ?: '—') : 'ایجاد نوشته جدید' }}
                    </h2>
                    @if ($isEditing)
                        <p class="mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                            شناسه: <span class="fa-digits">{{ number_format($post->id) }}</span>
                            — اسلاگ فعلی: <code class="text-[10px]" dir="ltr">{{ $post->slug }}</code>
                        </p>
                    @endif
                </div>
            </div>
            <a href="{{ route('np.posts.index') }}" wire:navigate class="ap-btn-ghost !py-2">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به لیست
            </a>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-4">

            {{-- ستون اصلی: تب‌ها --}}
            <div class="ap-card overflow-hidden lg:col-span-3" x-data="{ tab: 'base' }">
                <div class="flex gap-1 overflow-x-auto border-b border-line bg-surface-2 px-2 pt-2 dark:border-night-line dark:bg-night-3/50" role="tablist">
                    <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'base'"
                            x-bind:aria-selected="(tab === 'base').toString()" role="tab"
                            x-bind:class="tab === 'base' ? 'is-active' : ''">
                        <x-admin-panel::icon name="clipboard-list" class="size-4" />
                        اطلاعات کلی
                    </button>
                    @if (count($this->fildsList))
                        <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'filds'"
                                x-bind:aria-selected="(tab === 'filds').toString()" role="tab"
                                x-bind:class="tab === 'filds' ? 'is-active' : ''">
                            <x-admin-panel::icon name="list" class="size-4" />
                            فیلدهای اختصاصی
                        </button>
                    @endif
                    <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'seo'"
                            x-bind:aria-selected="(tab === 'seo').toString()" role="tab"
                            x-bind:class="tab === 'seo' ? 'is-active' : ''">
                        <x-admin-panel::icon name="globe" class="size-4" />
                        تنظیمات سئو
                    </button>
                    <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'media'"
                            x-bind:aria-selected="(tab === 'media').toString()" role="tab"
                            x-bind:class="tab === 'media' ? 'is-active' : ''">
                        <x-admin-panel::icon name="image" class="size-4" />
                        تصاویر و مدیا
                    </button>
                </div>

                {{-- ============ تب اطلاعات کلی ============ --}}
                <div x-show="tab === 'base'" x-cloak class="animate-fade-in space-y-5 p-5 sm:p-6">

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="postf-title">عنوان <span class="text-red-500">*</span></label>
                            <input id="postf-title" type="text" wire:model.blur="title" class="ap-input" placeholder="مثال: راهنمای خرید گوشی موبایل">
                            @error('title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="postf-slug">url</label>
                            <div class="flex gap-2">
                                <input id="postf-slug" type="text" wire:model="slug" dir="ltr"
                                       class="ap-input font-mono" placeholder="device-buying-guide">
                                <button type="button" wire:click="generateSlug" class="ap-btn-outline shrink-0 !px-3"
                                        title="ایجاد خودکار نامک از عنوان">
                                    <x-admin-panel::icon name="wand-2" class="size-4" />
                                </button>
                            </div>
                            @error('slug') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">دسته بندی ها <span class="text-red-500">*</span></label>
                            <div class="max-h-96 space-y-1.5 overflow-y-auto rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3">
                                @forelse ($this->categoryTree as $node)
                                    @php $category = $node['model']; @endphp
                                    <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-1.5 text-[13px] transition-colors hover:bg-surface-3 dark:hover:bg-night-2"
                                           style="margin-inline-start: {{ $node['depth'] * 20 }}px">
                                        <input type="checkbox" wire:model="categories" value="{{ $category->id }}"
                                               class="size-4 shrink-0 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                                        <span class="font-medium">{{ $category->title }}</span>
                                        @unless ($category->published)
                                            <span class="ap-badge bg-amber-50 text-[10px] text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">پیش‌نویس</span>
                                        @endunless
                                    </label>
                                @empty
                                    <p class="py-3 text-center text-xs text-ink-3 dark:text-night-ink-2">
                                        دسته‌بندی وبلاگی برای زبان فعلی ثبت نشده است.
                                    </p>
                                @endforelse
                            </div>
                            @error('categories') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            @error('categories.*') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="postf-publish-date">تاریخ انتشار</label>
                            <input id="postf-publish-date" type="text" wire:model="publish_date" data-jdp data-jdp-only-date="false"
                                   autocomplete="off" class="ap-input !py-2 fa-digits text-center" placeholder="۱۴۰۴/۰۱/۰۱ ۱۲:۰۰">
                            @error('publish_date') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">در صورت خالی بودن، تاریخ ایجاد به‌عنوان زمان ثبت ذخیره می‌شود.</p>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="postf-summary">خلاصه</label>
                        <textarea id="postf-summary" wire:model="summary" rows="3" class="ap-input" placeholder="خلاصه کوتاه نوشته برای نمایش در لیست‌ها…"></textarea>
                        @error('summary') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">محتوا</label>
                        <div class="overflow-hidden rounded-xl border border-line dark:border-night-line" wire:ignore>
                            <div id="ap-ckeditor" data-editor-value="{{ $description }}"></div>
                        </div>
                        <input type="hidden" wire:model="description" id="ap-ckeditor-input">
                        @error('description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- ============ تب فیلدهای اختصاصی ============ --}}
                @if (count($this->fildsList))
                    <div x-show="tab === 'filds'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            @foreach ($this->fildsList as $fild)
                                <div>
                                    <label class="mb-1.5 block text-[13px] font-semibold">
                                        {{ $fild->title }} @if ($fild->required) <span class="text-red-500">*</span> @endif
                                    </label>

                                    @if ($fild->type == 'input')
                                        <input type="text" wire:model="filds.{{ $fild->id }}" class="ap-input">
                                    @elseif ($fild->type == 'textarea')
                                        <textarea wire:model="filds.{{ $fild->id }}" rows="3" class="ap-input"></textarea>
                                    @elseif ($fild->type == 'number')
                                        <input type="number" wire:model="filds.{{ $fild->id }}" class="ap-input" step="any" dir="ltr">
                                    @elseif ($fild->type == 'email')
                                        <input type="email" wire:model="filds.{{ $fild->id }}" class="ap-input" dir="ltr">
                                    @elseif ($fild->type == 'colorPicker')
                                        <div class="flex items-center gap-2">
                                            <input type="color" wire:model="filds.{{ $fild->id }}"
                                                   class="size-9 shrink-0 cursor-pointer rounded-lg border border-line bg-transparent p-0.5 dark:border-night-line">
                                            <span class="text-xs text-ink-3 dark:text-night-ink-2" dir="ltr">{{ $this->filds[$fild->id] ?? '' ?: '—' }}</span>
                                        </div>
                                    @elseif ($fild->type == 'checkbox')
                                        <label class="flex cursor-pointer items-center gap-2.5 text-[13px]">
                                            <input type="checkbox" wire:model="filds.{{ $fild->id }}"
                                                   class="size-4 rounded-md border-line text-brand-600 dark:border-night-line">
                                            {{ $fild->title }}
                                        </label>
                                    @elseif ($fild->type == 'select')
                                        @php $selectOptions = array_filter(array_map('trim', explode(',', (string) $fild->select_options))); @endphp
                                        <select wire:model="filds.{{ $fild->id }}" class="ap-input">
                                            @if (! $fild->required)
                                                <option value="">—</option>
                                            @endif
                                            @foreach ($selectOptions as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    @endif

                                    @error('filds.' . $fild->id) <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- ============ تب سئو ============ --}}
                <div x-show="tab === 'seo'" x-cloak class="animate-fade-in space-y-5 p-5 sm:p-6">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="postf-meta-title">عنوان سئو</label>
                        <input id="postf-meta-title" type="text" wire:model="meta_title" class="ap-input" placeholder="عنوان نمایشی در نتایج جستجو…">
                        @error('meta_title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="postf-meta-description">توضیحات سئو</label>
                        <textarea id="postf-meta-description" wire:model="meta_description" rows="3" class="ap-input" placeholder="توضیحات متا (meta description)…"></textarea>
                        @error('meta_description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- ============ تب مدیا ============ --}}
                <div x-show="tab === 'media'" x-cloak class="animate-fade-in space-y-5 p-5 sm:p-6">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">نوع مقاله</label>
                        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border px-3 py-2.5 text-[13px] transition-colors
                                          {{ $post_type === 'text' ? 'border-brand-500 bg-brand-50 font-bold text-brand-700 dark:bg-brand-500/12 dark:text-brand-300' : 'border-line hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3' }}">
                                <input type="radio" wire:model.live="post_type" value="text" class="size-4 border-line text-brand-600 dark:border-night-line">
                                متنی
                            </label>
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border px-3 py-2.5 text-[13px] transition-colors
                                          {{ $post_type === 'video' ? 'border-brand-500 bg-brand-50 font-bold text-brand-700 dark:bg-brand-500/12 dark:text-brand-300' : 'border-line hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3' }}">
                                <input type="radio" wire:model.live="post_type" value="video" class="size-4 border-line text-brand-600 dark:border-night-line">
                                ویدیویی
                            </label>
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border px-3 py-2.5 text-[13px] transition-colors
                                          {{ $post_type === 'podcast' ? 'border-brand-500 bg-brand-50 font-bold text-brand-700 dark:bg-brand-500/12 dark:text-brand-300' : 'border-line hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3' }}">
                                <input type="radio" wire:model.live="post_type" value="podcast" class="size-4 border-line text-brand-600 dark:border-night-line">
                                پادکست
                            </label>
                        </div>
                        @error('post_type') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if ($post_type === 'video')
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="postf-video">لینک ویدیو</label>
                            <input id="postf-video" type="url" wire:model="video_url" dir="ltr" class="ap-input font-mono" placeholder="https://www.youtube.com/watch?v=...">
                            <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">از قسمت فایل‌ها می‌توانید فایل خود را بارگذاری کرده و لینک آن را اینجا قرار دهید.</p>
                            @error('video_url') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if ($post_type === 'podcast')
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="postf-podcast">لینک پادکست</label>
                            <input id="postf-podcast" type="url" wire:model="podcast_url" dir="ltr" class="ap-input font-mono" placeholder="https://example.com/podcast.mp3">
                            <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">از قسمت فایل‌ها می‌توانید فایل خود را بارگذاری کرده و لینک آن را اینجا قرار دهید.</p>
                            @error('podcast_url') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">تصویر شاخص</label>
                        <input type="file" accept="image/*" wire:model="image"
                               class="block w-full cursor-pointer rounded-xl border border-line bg-transparent text-xs text-ink-2
                                      file:me-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5
                                      file:text-xs file:font-semibold file:text-white hover:file:bg-brand-700 dark:border-night-line">
                        @error('image') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">بهترین اندازه ۱۲۰۰ پیکسل عرض میباشد. تصویر به‌صورت خودکار بهینه می‌شود.</p>
                    </div>

                    @if ($image)
                        <div class="flex items-center gap-3 rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3">
                            <img src="{{ $image->temporaryUrl() }}" alt="پیش‌نمایش تصویر جدید"
                                 class="size-16 rounded-lg border border-line object-cover dark:border-night-line">
                            <div class="text-xs">
                                <p class="font-semibold">تصویر جدید انتخاب‌شده</p>
                                <button type="button" wire:click="$set('image', null)"
                                        class="mt-0.5 flex items-center gap-1 font-semibold text-red-500 hover:underline">
                                    <x-admin-panel::icon name="x" class="size-3.5" />
                                    حذف فایل انتخاب‌شده
                                </button>
                            </div>
                        </div>
                    @elseif ($isEditing && $post->image)
                        <div class="flex items-center gap-3 rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3">
                            <img src="{{ asset($post->image) }}" alt="تصویر فعلی"
                                 class="size-16 rounded-lg border border-line object-cover dark:border-night-line">
                            <div class="text-xs">
                                <p class="font-semibold">تصویر فعلی</p>
                                <p class="mt-0.5 text-ink-3 dark:text-night-ink-2">با انتخاب فایل جدید جایگزین می‌شود.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ستون کنار: وضعیت انتشار --}}
            <div class="space-y-5 lg:col-span-1">
                <div class="ap-card space-y-2.5 p-5">
                    <h3 class="mb-1 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="settings" class="size-4 text-brand-600 dark:text-brand-400" />
                        وضعیت نوشته
                    </h3>
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-line px-3 py-2.5 text-[13px] transition-colors hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3">
                        <input type="checkbox" wire:model="published"
                               class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                        انتشار نوشته؟
                    </label>
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-line px-3 py-2.5 text-[13px] transition-colors hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3">
                        <input type="checkbox" wire:model="is_editor_pick"
                               class="size-4 rounded-md border-line text-amber-500 focus:ring-amber-500/30 dark:border-night-line">
                        انتخاب سردبیر؟
                    </label>
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-line px-3 py-2.5 text-[13px] transition-colors hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3">
                        <input type="checkbox" wire:model="allow_comments"
                               class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                        امکان ارسال دیدگاه؟
                    </label>
                </div>

                <div class="ap-card space-y-2 p-5 text-[12px] text-ink-3 dark:text-night-ink-2">
                    <p>ذخیره با دکمه پایین؛ پس از ذخیره به لیست نوشته‌ها بازمی‌گردید.</p>
                    @if ($isEditing)
                        <p>تاریخ ایجاد: <span class="fa-digits font-semibold">{{ \App\Livewire\Admin\Posts\PostForm::jdate($post->created_at, 'Y/m/d H:i') }}</span></p>
                    @endif
                </div>
            </div>
        </div>

        {{-- نوار ذخیره (چسبان) --}}
        <div class="ap-card sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 p-4 shadow-pop sm:p-5">
            <p class="text-[12px] text-ink-3 dark:text-night-ink-2">
                @if ($isEditing)
                    تغییرات با ذخیره بلافاصله اعمال می‌شود.
                @else
                    نوشته با وضعیت «{{ $published ? 'منتشر شده' : 'پیش‌نویس' }}» ثبت می‌شود.
                @endif
            </p>
            <div class="flex items-center gap-2">
                <a href="{{ route('np.posts.index') }}" wire:navigate class="ap-btn-ghost !py-2">انصراف</a>
                <button type="submit" class="ap-btn-primary !py-2.5" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">{{ $isEditing ? 'ذخیره تغییرات' : 'ایجاد نوشته' }}</span>
                    <span wire:loading wire:target="save" class="flex items-center justify-center gap-2">
                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                        در حال ذخیره…
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // راه‌اندازی مجدد CKEditor بعد از هر بار لود صفحه (سازگار با wire:navigate — الگوی products/category form)
    document.addEventListener('DOMContentLoaded', () => window.ApEditor?.init());
    document.addEventListener('livewire:navigated', () => window.ApEditor?.init());
    window.ApEditor?.init();
</script>
@endpush
