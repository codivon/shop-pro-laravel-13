<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * پشتیبان‌گیری و بازیابی سفارشی سایت — فاز ۱۸
 *
 * جایگزین جریان spatie/backup در صفحه‌ی جدید (کنترلر قدیمی دست‌نخورده):
 *  - بدون نیاز به mysqldump/هیچ باینری خارجی → دلیل خطای «پیغام موفق ولی بکاپ ساخته نمی‌شود»
 *    (mysqldump روی هاست‌های اشتراکی/ویندوز نبود و Job هم با صف غیر-sync اجرا نمی‌شد)
 *  - انتخابی: هر جدول و هر پوشه‌ی فایل جداگانه قابل انتخاب است (پیش‌فرض همه)
 *  - فرمت ZIP: manifest.json + database.sql + فایل‌ها با مسیر نسبی base_path
 *    (public/uploads/... و storage/app/public/...) → بازیابی دقیقاً به جای خودش
 *  - بازیابی: هیچ داده‌ای حذف یا بازنویسی نمی‌شود؛ جدول/فایل موجود → رد (skip) + گزارش کامل
 */
class SiteBackupService
{
    /** حداکثر حجم فایل‌های ZIP هنگام بازیابی (گارد بمب زیپ) */
    private const MAX_RESTORE_UNCOMPRESSED = 4 * 1024 * 1024 * 1024; // 4GB

    /** حداکثر تعداد ورودی‌های ZIP */
    private const MAX_ZIP_ENTRIES = 200000;

    /* ================================================================
     |  آمار و فهرست‌ها (برای مودال انتخابی)
     ================================================================ */

    /** همه‌ی جدول‌های دیتابیس + تعداد رکورد و حجم تقریبی */
    public function tables(): array
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $tables = [];

        if ($driver === 'mysql') {
            $rows = $connection->select(
                'SELECT TABLE_NAME AS name, TABLE_ROWS AS approx, data_length + index_length AS size
                 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME'
            );

            foreach ($rows as $row) {
                $name = $row->name;
                $rowsCount = (int) $row->approx;

                // TABLE_ROWS تقریبی است؛ برای دقت روی جداول کوچک COUNT واقعی
                if ($rowsCount < 5000) {
                    $rowsCount = (int) $connection->table($name)->count();
                }

                $tables[] = ['name' => $name, 'rows' => $rowsCount, 'size' => (int) $row->size];
            }
        } else {
            $names = $connection->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");

            foreach ($names as $row) {
                $tables[] = [
                    'name' => $row->name,
                    'rows' => (int) $connection->table($row->name)->count(),
                    'size' => 0,
                ];
            }
        }

        return $tables;
    }

    /**
     * گروه‌های فایل قابل پشتیبان‌گیری — هر زیرپوشه‌ی سطح اول یک گروه
     * ریشه‌ها: public/uploads (آپلودهای سایت) + storage/app/public (رسانه‌های مدیریت)
     */
    public function fileGroups(): array
    {
        $roots = [
            'public/uploads'       => 'آپلودهای سایت',
            'storage/app/public'   => 'رسانه‌های مدیریت',
        ];

        $groups = [];
        $totalFiles = 0;
        $totalSize = 0;

        foreach ($roots as $root => $rootLabel) {
            $absRoot = base_path($root);

            if (! is_dir($absRoot)) {
                continue;
            }

            // فایل‌های رها در ریشه‌ی خود root
            $rootFiles = $this->scanDir($absRoot, false);
            $rootSize = array_sum(array_column($rootFiles, 'size'));

            if (count($rootFiles) > 0) {
                $groups[] = [
                    'id' => $root,
                    'label' => '(فایل‌های ریشه)',
                    'root' => $root,
                    'root_label' => $rootLabel,
                    'files' => count($rootFiles),
                    'size' => $rootSize,
                    'images' => count(array_filter($rootFiles, fn ($f) => $this->isImage($f['path']))),
                ];
                $totalFiles += count($rootFiles);
                $totalSize += $rootSize;
            }

            foreach (new \DirectoryIterator($absRoot) as $item) {
                if ($item->isDot() || ! $item->isDir()) {
                    continue;
                }

                $files = $this->scanDir($item->getPathname(), true);
                $size = array_sum(array_column($files, 'size'));

                if (count($files) === 0) {
                    continue;
                }

                $groups[] = [
                    'id' => $root.'/'.$item->getFilename(),
                    'label' => $item->getFilename(),
                    'root' => $root,
                    'root_label' => $rootLabel,
                    'files' => count($files),
                    'size' => $size,
                    'images' => count(array_filter($files, fn ($f) => $this->isImage($f['path']))),
                ];
                $totalFiles += count($files);
                $totalSize += $size;
            }
        }

        usort($groups, fn ($a, $b) => [$a['root'], $a['label']] <=> [$b['root'], $b['label']]);

        return [
            'groups' => $groups,
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
        ];
    }

    /** فایل‌های موجود در storage/app/backups/incoming (برای بازیابی دستی فایل‌های حجیم) */
    public function incomingFiles(): array
    {
        $dir = storage_path('app/backups/incoming');

        if (! is_dir($dir)) {
            return [];
        }

        $files = [];

        foreach (glob($dir.'/*.zip') ?: [] as $path) {
            $files[] = [
                'name' => basename($path),
                'size' => filesize($path),
                'date' => filemtime($path),
            ];
        }

        usort($files, fn ($a, $b) => $b['date'] <=> $a['date']);

        return $files;
    }

    /* ================================================================
     |  ساخت بکاپ
     ================================================================ */

    /**
     * ساخت ZIP پشتیبان — [filename, path, size, tables, rows, files]
     *
     * @param array $tables نام جدول‌های انتخاب‌شده
     * @param array $groupIds شناسه‌ی گروه‌های فایل انتخاب‌شده (نسبی به base_path)
     */
    public function create(array $tables, array $groupIds): array
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        $disk = storage_path('app/backups');
        $tmpDir = $disk.'/tmp-'.uniqid();

        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        try {
            $groups = $this->fileGroups();
            $selectedGroups = array_values(array_filter($groups['groups'], fn ($g) => in_array($g['id'], $groupIds, true)));

            // ---- دامپ SQL (استریم روی فایل موقت) ----
            $sqlPath = $tmpDir.'/database.sql';
            $dumpedTables = $this->dumpDatabase($sqlPath, $tables);

            // ---- مانیفست ----
            $fileCount = array_sum(array_column($selectedGroups, 'files'));
            $fileBytes = array_sum(array_column($selectedGroups, 'size'));

            $manifest = [
                'app' => 'WeblakShop',
                'created_at' => now()->toDateTimeString(),
                'created_at_jalali' => $this->jalali(now()->timestamp, 'Y/m/d H:i'),
                'laravel' => app()->version(),
                'php' => PHP_VERSION,
                'database' => [
                    'driver' => DB::connection()->getDriverName(),
                    'name' => config('database.connections.'.config('database.default').'.database'),
                    'tables' => $dumpedTables,
                ],
                'files' => [
                    'groups' => array_map(fn ($g) => ['id' => $g['id'], 'files' => $g['files'], 'size' => $g['size']], $selectedGroups),
                    'count' => $fileCount,
                    'bytes' => $fileBytes,
                ],
                'version' => 2,
            ];

            $manifestPath = $tmpDir.'/manifest.json';
            file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            // ---- ZIP ----
            $filename = 'backup-'.now()->format('Ymd-His').'.zip';
            $zipPath = $disk.'/'.$filename;

            $zip = new \ZipArchive();

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('ساخت فایل ZIP ممکن نشد.');
            }

            $zip->addFile($manifestPath, 'manifest.json');
            $zip->addFile($sqlPath, 'database.sql');

            $addedFiles = 0;

            foreach ($selectedGroups as $group) {
                $absRoot = base_path($group['id']);
                $isRootGroup = $group['id'] === $group['root'];

                if (! is_dir($absRoot)) {
                    continue;
                }

                $files = $this->scanDir($absRoot, ! $isRootGroup);

                foreach ($files as $file) {
                    $relative = $group['id'].($isRootGroup ? '' : '/').$file['relative'];

                    if (@$zip->addFile($file['path'], ltrim($relative, '/'))) {
                        $addedFiles++;
                    }
                }
            }

            $zip->close();

            return [
                'filename' => $filename,
                'path' => $zipPath,
                'size' => filesize($zipPath) ?: 0,
                'tables' => count($dumpedTables),
                'rows' => array_sum(array_column($dumpedTables, 'rows')),
                'files' => $addedFiles,
            ];
        } finally {
            $this->rrmdir($tmpDir);
        }
    }

    /* ================================================================
     |  بازیابی
     ================================================================ */

    /**
     * بازیابی ZIP — هیچ داده‌ای حذف یا بازنویسی نمی‌شود:
     *  - جدول موجود → کل بلوک آن رد می‌شود (CREATE + INSERTها)
     *  - فایل موجود → رد می‌شود
     * خروجی: آرایه‌ی گزارش کامل (در last-restore.json هم ذخیره می‌شود)
     */
    public function restore(string $zipPath): array
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        $zip = new \ZipArchive();
        $open = $zip->open($zipPath);

        if ($open !== true) {
            throw new \RuntimeException('فایل ZIP قابل بازکردن نیست.');
        }

        try {
            $pdo = DB::connection()->getPdo();
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0;');

            $report = [
                'file' => basename($zipPath),
                'date' => now()->timestamp,
                'date_jalali' => $this->jalali(now()->timestamp, 'Y/m/d H:i'),
                'summary' => [
                    'rows_added' => 0,
                    'rows_skipped' => 0,
                    'rows_failed' => 0,
                    'tables_created' => 0,
                    'tables_skipped' => 0,
                    'tables_failed' => 0,
                    'files_added' => 0,
                    'files_skipped' => 0,
                    'files_failed' => 0,
                ],
                'tables' => [],
                'skipped_tables' => [],
                'failed_notes' => [],
                'manifest' => null,
            ];

            $tableStats = [];   // name => ['added','skipped','failed','created']
            $skipTable = [];    // جدول موجود → این بلوک کلاً رد شود

            // --- مانیفست (اختیاری) ---
            $manifestIndex = $zip->locateName('manifest.json');

            if ($manifestIndex !== false) {
                $decoded = json_decode((string) $zip->getFromIndex($manifestIndex), true);
                $report['manifest'] = is_array($decoded) ? [
                    'created_at_jalali' => $decoded['created_at_jalali'] ?? null,
                    'tables' => array_keys($decoded['database']['tables'] ?? []),
                ] : null;
            }

            // --- فهرست ورودی‌های ZIP + گاردهای امنیتی ---
            if ($zip->numFiles > self::MAX_ZIP_ENTRIES) {
                throw new \RuntimeException('تعداد فایل‌های داخل ZIP بیش از حد مجاز است.');
            }

            $sqlIndex = $zip->locateName('database.sql');
            $fileEntries = [];
            $totalUncompressed = 0;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);

                if ($stat === false || $stat['size'] <= 0) {
                    continue;
                }

                $totalUncompressed += $stat['size'];

                $entry = $this->normalizeZipEntry($stat['name']);

                if ($entry === null) {
                    continue; // مسیر نامعتبر / خطرناک
                }

                if ($entry === 'database.sql' || $entry === 'manifest.json') {
                    continue;
                }

                $fileEntries[] = ['index' => $i, 'entry' => $entry, 'size' => $stat['size']];
            }

            if ($totalUncompressed > self::MAX_RESTORE_UNCOMPRESSED) {
                throw new \RuntimeException('حجم کل محتوای ZIP بیش از حد مجاز است.');
            }

            // --- بازیابی فایل‌ها (skip-if-exists) ---
            foreach ($fileEntries as $entry) {
                $target = base_path($entry['entry']);

                if (! $this->isAllowedRestorePath($target)) {
                    $report['summary']['files_failed']++;
                    $report['failed_notes'][] = 'مسیر غیرمجاز: '.$entry['entry'];
                    continue;
                }

                if (is_file($target)) {
                    $report['summary']['files_skipped']++;
                    continue;
                }

                $dir = dirname($target);

                if (! is_dir($dir) && ! @mkdir($dir, 0775, true)) {
                    $report['summary']['files_failed']++;
                    $report['failed_notes'][] = 'ساخت پوشه ناموفق: '.$entry['entry'];
                    continue;
                }

                $stream = $zip->getStream($zip->getNameIndex($entry['index']));
                $out = @fopen($target, 'wb');

                if (! $stream || ! $out) {
                    $report['summary']['files_failed']++;

                    if ($stream) {
                        fclose($stream);
                    }
                    continue;
                }

                stream_copy_to_stream($stream, $out);
                fclose($stream);
                fclose($out);
                @chmod($target, 0664);
                $report['summary']['files_added']++;
            }

            // --- بازیابی دیتابیس ---
            if ($sqlIndex !== false) {
                $sql = (string) $zip->getFromIndex($sqlIndex);
                $statements = $this->splitSql($sql);

                foreach ($statements as $statement) {
                    $statement = trim($statement);

                    if ($statement === '') {
                        continue;
                    }

                    $upper = strtoupper($statement);

                    // CREATE TABLE
                    if (preg_match('/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i', $statement, $m)) {
                        $table = $m[1];
                        $this->ensureStats($tableStats, $table);

                        if (Schema::hasTable($table)) {
                            $skipTable[$table] = true;
                            $tableStats[$table]['created'] = false;
                            $report['summary']['tables_skipped']++;

                            if (! in_array($table, $report['skipped_tables'], true)) {
                                $report['skipped_tables'][] = $table;
                            }
                            continue;
                        }

                        try {
                            $pdo->exec($statement);
                            $tableStats[$table]['created'] = true;
                            $report['summary']['tables_created']++;
                        } catch (\Throwable $e) {
                            $report['summary']['tables_failed']++;
                            $report['failed_notes'][] = "CREATE {$table}: ".$this->shortError($e);
                        }
                        continue;
                    }

                    // INSERT
                    if (preg_match('/^INSERT\s+(?:IGNORE\s+)?INTO\s+`?([a-zA-Z0-9_]+)`?/i', $statement, $m)) {
                        $table = $m[1];
                        $this->ensureStats($tableStats, $table);
                        $rowCount = $this->countInsertRows($statement);

                        // فقط جدول‌های «موجود از قبل» رد می‌شوند؛ جدولی که همین حالا
                        // ساخته شد (skipTable تنظیم نشده) باید داده‌هایش درج شود
                        if (! empty($skipTable[$table])) {
                            $tableStats[$table]['skipped'] += $rowCount;
                            $report['summary']['rows_skipped'] += $rowCount;

                            if (! in_array($table, $report['skipped_tables'], true)) {
                                $report['skipped_tables'][] = $table;
                            }
                            continue;
                        }

                        try {
                            $pdo->exec($statement);
                            $tableStats[$table]['added'] += $rowCount;
                            $report['summary']['rows_added'] += $rowCount;
                        } catch (\Throwable $e) {
                            $tableStats[$table]['failed'] += $rowCount;
                            $report['summary']['rows_failed'] += $rowCount;
                            $report['failed_notes'][] = "INSERT {$table}: ".$this->shortError($e);
                        }
                        continue;
                    }

                    // سایر دستورات — فقط SET اجرا می‌شود؛ DROP هرگز (بازیابی هیچ چیز را حذف نمی‌کند؛
                    // DROP در دامپ فقط برای ایمپورت دستی خارجی مثل phpMyAdmin مفید است)
                    if (str_starts_with($upper, 'SET')) {
                        try {
                            $pdo->exec($statement);
                        } catch (\Throwable) {
                            // بی‌اهمیت
                        }
                    }
                }
            }

            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');

            // تبدیل آمار جدول‌ها به ردیف‌های گزارش
            foreach ($tableStats as $name => $stats) {
                $report['tables'][] = [
                    'name' => $name,
                    'added' => $stats['added'],
                    'skipped' => $stats['skipped'],
                    'modified' => 0,
                    'failed' => $stats['failed'],
                    'created' => $stats['created'],
                ];
            }

            usort($report['tables'], fn ($a, $b) => strcmp($a['name'], $b['name']));

            // محدود کردن یادداشت‌ها
            $report['failed_notes'] = array_slice(array_unique($report['failed_notes']), 0, 30);

            // ذخیره گزارش آخرین بازیابی
            $reportDir = storage_path('app/backups');
            file_put_contents($reportDir.'/last-restore.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return $report;
        } finally {
            $zip->close();
        }
    }

    /** گزارش آخرین بازیابی ذخیره‌شده */
    public function lastRestoreReport(): ?array
    {
        $path = storage_path('app/backups/last-restore.json');

        if (! is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    /* ================================================================
     |  دامپ دیتابیس
     ================================================================ */

    /** دامپ استریم‌شده → [table => ['rows' => n, 'create' => bool]] */
    private function dumpDatabase(string $sqlPath, array $tables): array
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $pdo = $connection->getPdo();
        $handle = fopen($sqlPath, 'wb');

        if (! $handle) {
            throw new \RuntimeException('ساخت فایل دامپ ممکن نشد.');
        }

        $header = "-- WeblakShop Site Backup\n"
            .'-- Date: '.now()->toDateTimeString()."\n"
            ."-- Generator: SiteBackupService v2 (PHP-only, بدون باینری خارجی)\n"
            ."SET NAMES utf8mb4;\n"
            ."SET FOREIGN_KEY_CHECKS = 0;\n\n";
        fwrite($handle, $header);

        $result = [];

        try {
            foreach ($tables as $table) {
                $table = (string) $table;

                if (! preg_match('/^[a-zA-Z0-9_]+$/', $table) || ! Schema::hasTable($table)) {
                    continue;
                }

                fwrite($handle, "\n-- ================= TABLE: {$table} =================\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");

                // ساختار — ستون ۱ = دستور CREATE کامل (ستون ۰ نام جدول است!)
                $createSql = null;

                if ($driver === 'mysql') {
                    $row = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_NUM);
                    $createSql = $row[1] ?? null;
                } else {
                    $stmt = $pdo->prepare("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?");
                    $stmt->execute([$table]);
                    $createSql = $stmt->fetchColumn();
                }

                if ($createSql) {
                    fwrite($handle, rtrim($createSql, ";\r\n").";\n");
                }

                // داده‌ها — هر ردیف یک INSERT (شمارش دقیق در بازیابی)
                $rows = 0;
                $stmt = $pdo->query("SELECT * FROM `{$table}`");

                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $columns = array_keys($row);
                    $cols = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));
                    $values = implode(', ', array_map(fn ($v) => $this->quoteValue($pdo, $v), array_values($row)));

                    fwrite($handle, "INSERT INTO `{$table}` ({$cols}) VALUES ({$values});\n");
                    $rows++;
                }

                $result[$table] = ['rows' => $rows, 'create' => (bool) $createSql];
            }

            fwrite($handle, "\nSET FOREIGN_KEY_CHECKS = 1;\n");
        } finally {
            fclose($handle);
        }

        return $result;
    }

    private function quoteValue(\PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        return $pdo->quote((string) $value);
    }

    /* ================================================================
     |  پارسر SQL و ابزارها
     ================================================================ */

    /** جداسازی دستورات SQL با دقت به نقل‌قول‌ها و کامنت‌ها */
    private function splitSql(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $length = strlen($sql);
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            // کامنت‌های خطی
            if (! $inSingle && ! $inDouble && ! $inBacktick && $char === '-' && $next === '-') {
                $lineEnd = strpos($sql, "\n", $i);
                $i = $lineEnd === false ? $length : $lineEnd - 1;
                continue;
            }

            if (! $inSingle && ! $inDouble && ! $inBacktick && $char === '/' && $next === '*') {
                $end = strpos($sql, '*/', $i + 2);
                $i = $end === false ? $length : $end + 1;
                continue;
            }

            if ($char === "'" && ! $inDouble && ! $inBacktick) {
                // '' داخل رشته = escape
                if ($inSingle && $next === "'") {
                    $buffer .= $char.$next;
                    $i++;
                    continue;
                }
                $inSingle = ! $inSingle;
            } elseif ($char === '"' && ! $inSingle && ! $inBacktick) {
                $inDouble = ! $inDouble;
            } elseif ($char === '`' && ! $inSingle && ! $inDouble) {
                $inBacktick = ! $inBacktick;
            } elseif ($char === '\\' && $inSingle) {
                $buffer .= $char.$next;
                $i++;
                continue;
            }

            if ($char === ';' && ! $inSingle && ! $inDouble && ! $inBacktick) {
                $trimmed = trim($buffer);

                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $trimmed = trim($buffer);

        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }

        return $statements;
    }

    /** شمارش ردیف‌های یک INSERT (گروه‌های VALUE سطح بالا) */
    private function countInsertRows(string $statement): int
    {
        $valuesPos = stripos($statement, ' VALUES ');

        if ($valuesPos === false) {
            $valuesPos = stripos($statement, ' VALUE ');
        }

        if ($valuesPos === false) {
            return 1;
        }

        $values = substr($statement, $valuesPos + 8);
        $rows = 1;
        $depth = 0;
        $inSingle = false;
        $inBacktick = false;
        $length = strlen($values);

        for ($i = 0; $i < $length; $i++) {
            $char = $values[$i];

            if ($char === "'" && ! $inBacktick) {
                if ($inSingle && ($values[$i + 1] ?? '') === "'") {
                    $i++;
                    continue;
                }
                $inSingle = ! $inSingle;
            } elseif ($char === '\\' && $inSingle) {
                $i++;
            } elseif ($char === '`' && ! $inSingle) {
                $inBacktick = ! $inBacktick;
            } elseif (! $inSingle && ! $inBacktick) {
                if ($char === '(') {
                    $depth++;
                } elseif ($char === ')') {
                    $depth--;
                } elseif ($char === ',' && $depth === 0) {
                    $rows++;
                }
            }
        }

        return max(1, $rows);
    }

    /* ================================================================
     |  ابزار فایل
     ================================================================ */

    /** اسکن یک پوشه — [path, relative, size] */
    private function scanDir(string $dir, bool $recursive): array
    {
        $files = [];

        try {
            $iterator = $recursive
                ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::LEAVES_ONLY)
                : new \IteratorIterator(new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS));

            foreach ($iterator as $item) {
                if (! $item->isFile()) {
                    continue;
                }

                $full = $item->getPathname();
                $relative = ltrim(str_replace('\\', '/', substr($full, strlen(rtrim($dir, '/\\')))), '/');

                $files[] = [
                    'path' => $full,
                    'relative' => $relative,
                    'size' => $item->getSize(),
                ];
            }
        } catch (\Throwable) {
            // پوشه‌های بدون دسترسی نادیده گرفته می‌شوند
        }

        return $files;
    }

    private function isImage(string $path): bool
    {
        return (bool) preg_match('/\.(jpe?g|png|gif|webp|avif|svg|bmp|ico)$/i', $path);
    }

    /** نرمال‌سازی نام ورودی ZIP + گارد zip-slip (خروجی مسیر نسبی امن یا null) */
    private function normalizeZipEntry(string $name): ?string
    {
        $name = str_replace('\\', '/', $name);

        if ($name === '' || str_contains($name, '..') || str_starts_with($name, '/') || str_contains($name, "\0")) {
            return null;
        }

        // فقط مسیرهای public/ و storage/ پذیرفته می‌شوند
        if (! preg_match('#^(public|storage)/[A-Za-z0-9_\-./ ]+$#', $name)) {
            return null;
        }

        return preg_replace('#/+#', '/', rtrim($name, '/'));
    }

    /** گارد نهایی مسیر مقصد بازیابی — فقط داخل public و storage */
    private function isAllowedRestorePath(string $absolute): bool
    {
        $realBase = realpath(base_path());

        if ($realBase === false) {
            return false;
        }

        $parent = dirname($absolute);

        // پوشه ممکن است هنوز موجود نباشد → اولین والد موجود را real می‌کنیم
        $probe = $parent;

        while (! is_dir($probe)) {
            $probe = dirname($probe);
        }

        $realProbe = realpath($probe);

        if ($realProbe === false || ! str_starts_with($realProbe, $realBase.DIRECTORY_SEPARATOR)) {
            return false;
        }

        $allowed = [realpath(base_path('public')), realpath(storage_path())];

        foreach ($allowed as $base) {
            if ($base !== false && (str_starts_with($realProbe, $base.DIRECTORY_SEPARATOR) || $realProbe === $base)) {
                return true;
            }
        }

        return false;
    }

    private function ensureStats(array &$stats, string $table): void
    {
        if (! isset($stats[$table])) {
            $stats[$table] = ['added' => 0, 'skipped' => 0, 'failed' => 0, 'created' => false];
        }
    }

    private function shortError(\Throwable $e): string
    {
        return mb_substr($e->getMessage(), 0, 180);
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        ) as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($dir);
    }

    /** تاریخ شمسی (بدون وابستگی به helper قدیمی) */
    private function jalali(int $timestamp, string $format): string
    {
        try {
            return \Morilog\Jalali\Jalalian::fromDateTime(\Carbon\Carbon::createFromTimestamp($timestamp))->format($format);
        } catch (\Throwable) {
            return date('Y/m/d H:i', $timestamp);
        }
    }
}
