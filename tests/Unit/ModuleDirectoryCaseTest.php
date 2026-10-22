<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * سلامت ساختار ماژول‌ها — کلاس باگ seoAudit:
 * نام پوشه ماژول باید دقیقاً با فیلد name در module.json یکی باشد.
 * روی لینوکس (فایل‌سیستم case-sensitive) مغایرت حالت حروف یعنی autoloader
 * کلاس‌های ماژول (Modules\SeoAudit\...) را پیدا نمی‌کند → سرویس‌پروایدر،
 * روت‌ها و منوی ماژول اصلاً لود نمی‌شوند (بدون هیچ ارور واضحی).
 */
class ModuleDirectoryCaseTest extends TestCase
{
    private string $modulesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulesPath = dirname(__DIR__, 2).'/Modules';

        if (! is_dir($this->modulesPath)) {
            $this->markTestSkipped('پوشه Modules وجود ندارد.');
        }
    }

    public function test_every_module_directory_name_matches_module_json_name(): void
    {
        $mismatches = [];

        foreach (glob($this->modulesPath.'/*/module.json') ?: [] as $manifestFile) {
            $meta = json_decode((string) file_get_contents($manifestFile), true);

            if (! is_array($meta) || empty($meta['name'])) {
                $mismatches[] = basename(dirname($manifestFile)).' → module.json بدون نام معتبر';
                continue;
            }

            $directory = basename(dirname($manifestFile));

            if ($directory !== $meta['name']) {
                $mismatches[] = sprintf(
                    'پوشه «%s» با نام module.json «%s» یکی نیست (autoload روی لینوکس خراب می‌شود)',
                    $directory,
                    $meta['name']
                );
            }
        }

        $this->assertSame([], $mismatches, implode("\n", $mismatches));
    }

    public function test_modules_statuses_file_keys_match_module_names(): void
    {
        $statusesFile = dirname(__DIR__, 2).'/modules_statuses.json';

        if (! is_file($statusesFile)) {
            $this->markTestSkipped('modules_statuses.json هنوز ساخته نشده است.');
        }

        $statuses = json_decode((string) file_get_contents($statusesFile), true);

        if (! is_array($statuses)) {
            $this->fail('modules_statuses.json معتبر نیست (JSON نامعتبر).');
        }

        $declared = [];

        foreach (glob($this->modulesPath.'/*/module.json') ?: [] as $manifestFile) {
            $meta = json_decode((string) file_get_contents($manifestFile), true);

            if (is_array($meta) && ! empty($meta['name'])) {
                $declared[] = $meta['name'];
            }
        }

        $orphans = array_diff(array_keys($statuses), $declared);

        $this->assertSame(
            [],
            array_values($orphans),
            'کلیدهای modules_statuses.json باید با نام module.json ماژول‌ها یکی باشند: '.implode(', ', $orphans)
        );
    }

    public function test_module_providers_declared_in_manifest_are_loadable(): void
    {
        // فقط ماژول‌هایی که فایل سرویس‌پروایدرشان واقعاً روی دیسک هست را چک می‌کنیم
        // (الزام PSR-4: Modules\{Name}\Providers\{Name}ServiceProvider → Modules/{Name}/app/Providers/...)
        $unresolvable = [];

        foreach (glob($this->modulesPath.'/*/module.json') ?: [] as $manifestFile) {
            $meta = json_decode((string) file_get_contents($manifestFile), true);
            $directory = basename(dirname($manifestFile));

            foreach ((array) ($meta['providers'] ?? []) as $provider) {
                // Modules\SeoAudit\Providers\X → Providers/X (حذف پیشوند Modules\{Name}\)
                $relative = str_replace('\\', '/', $provider);
                $relative = preg_replace(
                    '#^Modules/'.preg_quote($meta['name'] ?? $directory, '#').'/#',
                    '',
                    $relative,
                    1,
                    $strippedPrefix
                );

                if (! $strippedPrefix) {
                    $unresolvable[] = $provider.' (namespace با الگوی Modules\{Name}\ هم‌خوان نیست — پوشه: '.$directory.')';
                    continue;
                }

                $candidates = [
                    $this->modulesPath.'/'.$directory.'/app/'.$relative.'.php',
                    $this->modulesPath.'/'.$directory.'/'.$relative.'.php',
                ];

                $found = false;

                foreach ($candidates as $file) {
                    if (is_file($file)) {
                        $found = true;
                        break;
                    }
                }

                if (! $found) {
                    $unresolvable[] = $provider.' (پوشه: '.$directory.')';
                }
            }
        }

        $this->assertSame([], $unresolvable, 'فایل سرویس‌پروایدر پیدا نشد: '.implode(', ', $unresolvable));
    }
}
