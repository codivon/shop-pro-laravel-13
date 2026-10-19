<?php

namespace App\Livewire\Admin\System;

use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * لاگ سیستم — بازسازی امن LaravelLogViewer (روت قدیمی logs.index در گروه CheckCreator)
 *
 * - فقط خواندن storage/logs/*.log (بدون اجرای هیچ کدی)
 * - نام فایل همیشه basename می‌شود (ضد مسیردهی)
 * - پارس سطح/تاریخ/پیام + فیلتر سطح + فقط ۵۰۰ رکورد آخر با دکمه «بیشتر»
 */
#[Title('لاگ سیستم')]
class LogsIndex extends Component
{
    /** حداکثر رکوردهای پارس‌شده (طبق spec: ۵۰۰ خط آخر) */
    public const MAX_ENTRIES = 500;

    /** سقف حجم خواندن از انتهای فایل (۳ مگابایت) */
    public const MAX_READ_BYTES = 3145728;

    public string $file = 'laravel.log';

    public string $level = '';

    public int $limit = 100;

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

    public function selectFile(string $name): void
    {
        $this->file = basename($name);
        $this->limit = 100;
    }

    /* ---------------- پارس امن لاگ ---------------- */

    public function getEntriesProperty()
    {
        $path = storage_path('logs/' . basename($this->file));

        if (!is_file($path)) {
            return collect();
        }

        $size = filesize($path);

        // سقف vendor قدیمی (config logviewer.max_file_size) + برش انتهای فایل
        $maxSize = (int) config('logviewer.max_file_size', 52428800);
        if ($size > $maxSize) {
            return collect();
        }

        $readBytes = min($size, self::MAX_READ_BYTES);
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return collect();
        }
        fseek($handle, -$readBytes, SEEK_END);
        $content = fread($handle, $readBytes);
        fclose($handle);

        // برش نیمه‌ی اولین خط (احتمال برخورد وسط خط به دلیل خواندن tail)
        $newline = strpos($content, "\n");
        if ($newline !== false) {
            $content = substr($content, $newline + 1);
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

        // جدیدترین اول (مثل vendor قدیمی)
        $entries = array_reverse($entries);

        // فیلتر سطح
        if ($this->level !== '') {
            $entries = array_values(array_filter(
                $entries,
                fn ($e) => $e['level'] === $this->level
            ));
        }

        return collect($entries);
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

        return [
            'date'    => $m[1],
            'env'     => strtolower($m[2]),
            'level'   => strtolower($m[3]),
            'message' => trim($m[4]),
            'stack'   => $stack,
        ];
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

    /** شمارنده هر سطح (برای بج‌های فیلتر) */
    public function getLevelCountsProperty(): array
    {
        $counts = [];
        foreach ($this->entries as $entry) {
            $counts[$entry['level']] = ($counts[$entry['level']] ?? 0) + 1;
        }

        return $counts;
    }

    /* ---------------- اکشن‌ها ---------------- */

    public function updatedLevel(): void
    {
        $this->limit = 100;
    }

    public function loadMore(): void
    {
        $this->limit = min($this->limit + 200, self::MAX_ENTRIES);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.system.logs')
            ->layout('admin-panel::layout');
    }
}
