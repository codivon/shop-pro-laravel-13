<?php

namespace App\Livewire\Admin\System;

use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * لاگ های سیستم — فاز ۲۰: بازطراحی کامل ترمینالی مثل نمونه‌ی کاربر
 *
 * - خواندن امن storage/logs/*.log (بدون اجرای هیچ کدی) — نام فایل همیشه basename
 * - جدیدترین اول + فیلتر سطح + جستجو + مرتب‌سازی
 * - دانلود لاگ + پاک کردن کل لاگ + حذف هر رکورد / حذف انتخاب‌شده‌ها (بازنویسی امن فایل)
 * - رکوردها با «اثر انگشت» (uid) شناسایی می‌شوند تا حذف بر اساس ایندکسِ نمایش
 *   بعد از تغییر فیلترها همیشه همان رکورد واقعی را از فایل پاک کند
 */
#[Title('لاگ های سیستم')]
class LogsIndex extends Component
{
    /** حداکثر رکوردهای پارس‌شده (طبق spec: ۵۰۰ خط آخر) */
    public const MAX_ENTRIES = 500;

    /** سقف حجم خواندن از انتهای فایل (۳ مگابایت) */
    public const MAX_READ_BYTES = 3145728;

    public string $file = 'laravel.log';

    public string $level = '';

    /** فاز ۲۰ — جستجو در متن رکوردها */
    public string $search = '';

    /** فاز ۲۰ — مرتب‌سازی: newest | oldest */
    public string $sort = 'newest';

    public int $limit = 100;

    /* (فاز ۲۰) انتخاب رکوردها سمت کلاینت (Alpine) نگه‌داری می‌شود — درس فاز ۱۷:
       wire:model آرایه‌ایِ deferred تا commit بعدی سینک نمی‌شود و UI فوری به‌روز نمی‌شود */

    public function mount(): void
    {
        // پورت CheckCreator قدیمی (روت logs.index داخل گروه CheckCreator بود)
        $admin = auth('adminPanel')->user();

        (!$admin || $admin->level != 'creator') ? abort(403) : null;

        // اگر فایل پیش‌فرض وجود ندارد، اولین فایل موجود
        if (!$this->fileExists($this->file) && count($this->files) > 0) {
            $this->file = $this->files->first()['name'];
        }
    }

    /* ---------------- فایل‌های لاگ (پورت getFiles قدیمی) ---------------- */

    public function getFilesProperty()
    {
        $pattern = config('logviewer.pattern', '*.log');
        $files = glob(storage_path('logs') . '/' . $pattern);

        $out = [];
        foreach ($files ?: [] as $path) {
            if (!is_file($path)) {
                continue;
            }

            $out[] = [
                'name'     => basename($path),
                'size'     => filesize($path),
                'modified' => filemtime($path),
            ];
        }

        return collect($out)->sortByDesc('modified')->values();
    }

    private function fileExists(string $name): bool
    {
        return $this->files->contains(fn ($f) => $f['name'] === $name);
    }

    /** حجم فایل جاری برای سربرگ ترمینال */
    public function getCurrentFileSizeProperty(): int
    {
        return (int) ($this->files->firstWhere('name', $this->file)['size'] ?? 0);
    }

    public function selectFile(string $name): void
    {
        $this->file = basename($name);
        $this->limit = 100;
    }

    /* ---------------- پارس امن لاگ ---------------- */

    /** مسیر امن فایل جاری (basename — ضد مسیردهی) */
    private function filePath(): string
    {
        return storage_path('logs/' . basename($this->file));
    }

    public function getEntriesProperty()
    {
        $path = $this->filePath();

        if (!is_file($path)) {
            return collect();
        }

        // فاز ۲۰ — کش stat در پروسه‌های طول‌عمر (php -S) می‌ماند؛ حذف فایل بازنویسی‌شده
        // نباید حجم/وضعیت قدیمی را برگرداند
        clearstatcache(true, $path);

        $size = filesize($path);

        if ($size === false || $size === 0) {
            return collect();
        }

        // سقف vendor قدیمی (config logviewer.max_file_size)
        $maxSize = (int) config('logviewer.max_file_size', 52428800);
        if ($size > $maxSize) {
            return collect();
        }

        $readBytes = (int) min($size, self::MAX_READ_BYTES);

        if ($readBytes <= 0) {
            return collect();
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return collect();
        }
        if (fseek($handle, -$readBytes, SEEK_END) !== 0) {
            // tail-read ناموفق (فایل کوچک‌تر از حد انتظار — مثلاً تازه بازنویسی شده) — از ابتدا بخوان
            rewind($handle);
        }
        $content = (string) fread($handle, $readBytes);
        fclose($handle);

        // برش نیمه‌ی اولین خط — فقط وقتی tail-read انجام شده (اگر کل فایل خوانده شده،
        // این برش اولین رکورد کامل را نابود می‌کرد — فاز ۲۰)
        if ($readBytes < $size) {
            $newline = strpos($content, "\n");
            if ($newline !== false) {
                $content = substr($content, $newline + 1);
            }
        }

        // پارس بر اساس هدینگ استاندارد لاراول (الگوی vendor قدیمی)
        $headingPattern = '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}([+-]\d{4})?\]\s*\w+\.\w+:/';
        preg_match_all($headingPattern, $content, $headings, PREG_OFFSET_CAPTURE);

        $entries = [];
        $count = count($headings[0]);

        for ($i = 0; $i < $count; $i++) {
            $start = $headings[0][$i][1];
            $end = $i + 1 < $count ? $headings[0][$i + 1][1] : strlen($content);
            $raw = substr($content, $start, $end - $start);

            $entry = $this->parseEntry($raw);
            if ($entry !== null) {
                $entries[] = $entry;
            }
        }

        // جدیدترین اول (پیش‌فرض)
        $entries = array_reverse($entries);

        // فیلتر سطح
        if ($this->level !== '') {
            $entries = array_values(array_filter(
                $entries,
                fn ($e) => $e['level'] === $this->level
            ));
        }

        // فاز ۲۰ — جستجو در متن (پیام + جزئیات + تاریخ + سطح)
        if (trim($this->search) !== '') {
            $needle = mb_strtolower(trim($this->search));
            $entries = array_values(array_filter(
                $entries,
                fn ($e) => str_contains(mb_strtolower($e['message'].' '.$e['stack'].' '.$e['date'].' '.$e['level']), $needle)
            ));
        }

        // فاز ۲۰ — مرتب‌سازی + ایندکس پایدار برای نمایش
        if ($this->sort === 'oldest') {
            $entries = array_reverse($entries);
        }

        return collect($entries)->values();
    }

    private function parseEntry(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $newline = strpos($raw, "\n");
        $firstLine = $newline === false ? $raw : substr($raw, 0, $newline);
        $stack = $newline === false ? '' : trim(substr($raw, $newline + 1));

        // [2024-01-01 10:00:00] production.ERROR: message {"context"}
        if (!preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s*(\w+)\.(\w+):\s*(.*)$/s', $firstLine, $m)) {
            return null;
        }

        $entry = [
            'date'    => $m[1],
            'env'     => strtolower($m[2]),
            'level'   => strtolower($m[3]),
            'message' => trim($m[4]),
            'stack'   => $stack,
        ];

        // فاز ۲۰ — اثر انگشت رکورد برای حذف دقیق از فایل (مستقل از فیلترهای نمایش)
        $entry['uid'] = md5($entry['date'].'|'.$entry['env'].'|'.$entry['level'].'|'.$entry['message'].'|'.$entry['stack']);

        return $entry;
    }

    /** رکوردهای نمایش‌داده‌شده (slice بر اساس limit) */
    public function getVisibleEntriesProperty()
    {
        return $this->entries->take($this->limit)->values();
    }

    public function getTotalCountProperty(): int
    {
        return $this->entries->count();
    }

    /** شمارنده هر سطح (برای بج‌های فیلتر) — روی کل رکوردهای پارس‌شده */
    public function getLevelCountsProperty(): array
    {
        $counts = [];
        foreach ($this->entries as $entry) {
            $counts[$entry['level']] = ($counts[$entry['level']] ?? 0) + 1;
        }

        return $counts;
    }

    /* ---------------- فاز ۲۰ — دانلود / پاک‌سازی / حذف رکورد ---------------- */

    private function guardCreator(): void
    {
        $admin = auth('adminPanel')->user();

        (!$admin || $admin->level != 'creator') ? abort(403) : null;
    }

    /** دانلود فایل لاگ جاری (پشتیبانی داخلی Livewire 4 برای BinaryFileResponse) */
    public function downloadLog(): mixed
    {
        $this->guardCreator();

        $path = $this->filePath();

        if (!is_file($path)) {
            abort(404);
        }

        return response()->download($path, basename($this->file));
    }

    /** پاک کردن کامل لاگ جاری */
    public function clearLog(): void
    {
        $this->guardCreator();

        $path = $this->filePath();

        if (!is_file($path)) {
            $this->dispatch('toast', message: 'فایل لاگ یافت نشد.', type: 'error');

            return;
        }

        file_put_contents($path, '');
        $this->limit = 100;

        $this->dispatch('toast', message: 'فایل لاگ «'.$this->file.'» با موفقیت پاک شد.', type: 'success');
    }

    /**
     * حذف رکوردها بر اساس uid (اثر انگشت) با بازنویسی امن فایل.
     * uid به‌جای ایندکس: بعد از تغییر فیلتر/جستجو همیشه همان رکورد واقعی حذف می‌شود.
     * رکوردهای تکراری (محتوای یکسان) به تعداد انتخاب‌شده حذف می‌شوند.
     */
    public function deleteEntries(array $uids): void
    {
        $this->guardCreator();

        $uids = array_values(array_unique(array_filter(array_map('strval', $uids))));

        if (empty($uids)) {
            return;
        }

        $path = $this->filePath();

        if (!is_file($path)) {
            $this->dispatch('toast', message: 'فایل لاگ یافت نشد.', type: 'error');

            return;
        }

        $maxSize = (int) config('logviewer.max_file_size', 52428800);

        clearstatcache(true, $path);

        if (filesize($path) > $maxSize) {
            $this->dispatch('toast', message: 'فایل لاگ برای حذف رکورد بسیار حجیم است؛ از «پاک کردن لاگ» استفاده کنید.', type: 'error');

            return;
        }

        $content = (string) file_get_contents($path);

        if ($content === '') {
            $this->dispatch('toast', message: 'فایل لاگ خالی است؛ رکوردی برای حذف نیست.', type: 'error');

            return;
        }

        $headingPattern = '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}([+-]\d{4})?\]\s*\w+\.\w+:/';
        preg_match_all($headingPattern, $content, $headings, PREG_OFFSET_CAPTURE);

        // چندمجموعه‌ی uidهای درخواستی (تعداد تکرار مهم است)
        $wanted = array_count_values($uids);
        $removeRanges = [];

        $count = count($headings[0]);
        for ($i = 0; $i < $count; $i++) {
            $start = $headings[0][$i][1];
            $end = $i + 1 < $count ? $headings[0][$i + 1][1] : strlen($content);
            $raw = substr($content, $start, $end - $start);

            $entry = $this->parseEntry($raw);

            if ($entry !== null && ($wanted[$entry['uid']] ?? 0) > 0) {
                $removeRanges[] = [$start, $end];
                $wanted[$entry['uid']]--;
            }
        }

        if (empty($removeRanges)) {
            $this->dispatch('toast', message: 'رکوردی برای حذف در فایل پیدا نشد (ممکن است قبلاً حذف شده باشد).', type: 'error');

            return;
        }

        // ساخت محتوای جدید بدون رکوردهای حذف‌شده
        $newContent = '';
        $cursor = 0;
        foreach ($removeRanges as [$start, $end]) {
            $newContent .= substr($content, $cursor, $start - $cursor);
            $cursor = $end;
        }
        $newContent .= substr($content, $cursor);

        // نوشتن اتمی (فایل موقت + rename)
        $tmp = $path.'.tmp-'.uniqid();

        if (file_put_contents($tmp, $newContent) === false || !rename($tmp, $path)) {
            @unlink($tmp);
            $this->dispatch('toast', message: 'نوشتن مجدد فایل لاگ ناموفق بود.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: number_format(count($removeRanges)).' رکورد از لاگ حذف شد.', type: 'success');
    }

    /* ---------------- ابزارها ---------------- */

    public function updatedLevel(): void
    {
        $this->limit = 100;
    }

    public function updatedSearch(): void
    {
        $this->limit = 100;
    }

    public function updatedSort(): void
    {
        $this->limit = 100;
    }

    public function loadMore(): void
    {
        $this->limit = min($this->limit + 200, self::MAX_ENTRIES);
    }

    public static function faKb(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        return number_format($bytes / 1024, 1).' KB';
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.system.logs')
            ->layout('admin-panel::layout');
    }
}
