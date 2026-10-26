<?php

namespace Tests\Unit;

use App\Services\PackageApiService;
use App\Services\PackageInstallerService;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;
use ZipArchive;

/**
 * نام کانونی ماژول هنگام استخراج ZIP — ریشه باگ seoAudit:
 * پوشه داخل ZIP ممکن است هر حالت حروفی داشته باشد (seoAudit) اما نام صحیح
 * ماژول در module.json است (SeoAudit) و باید پوشه با همان نام کانونی ساخته شود
 * تا namespace کلاس‌ها (Modules\SeoAudit\...) روی لینوکس قابل autoload باشد.
 */
class PackageInstallerCanonicalNameTest extends TestCase
{
    private string $workspace;

    private PackageInstallerService $installer;

    private \ReflectionMethod $extractZip;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = sys_get_temp_dir().'/pkg-installer-test-'.uniqid();
        mkdir($this->workspace, 0777, true);

        config(['packages.modules.path' => $this->workspace.'/Modules']);

        $this->installer = new PackageInstallerService(
            app(PackageApiService::class),
            app(Filesystem::class)
        );

        $this->extractZip = new \ReflectionMethod($this->installer, 'extractZip');
        $this->extractZip->setAccessible(true);
    }

    /**
     * آیا فایل‌سیستم case-sensitive است؟
     * (ویندوز/macOS نه — دو پوشه که فقط در بزرگی/کوچکی حروف فرق دارند
     * نمی‌توانند هم‌زمان وجود داشته باشند؛ دو تستِ نام کانونی فقط روی
     * فایل‌سیستم case-sensitive (لینوکس) قابل شبیه‌سازی‌اند. بقیهٔ تست‌ها
     * — نصب مجدد/بایگانی/رد ZIP خراب — روی هر پلتفرمی بدون گارد اجرا می‌شوند.)
     */
    private function requireCaseSensitiveFilesystem(): void
    {
        $probe = $this->workspace.'/CaseProbe';
        @rmdir($probe);
        @mkdir($probe, 0777, true);

        $caseInsensitive = is_dir($this->workspace.'/caseprobe');

        @rmdir($probe);

        if ($caseInsensitive) {
            $this->markTestSkipped(
                'فایل‌سیستم case-insensitive است (ویندوز) — وجود هم‌زمان دو پوشه با تفاوت فقط در حالت حروف '
                .'قابل شبیه‌سازی نیست؛ این تست روی فایل‌سیستم case-sensitive (لینوکس) اجرا می‌شود.'
            );
        }
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->workspace);

        parent::tearDown();
    }

    /** ساخت ZIP تستی: پوشه ریشه با نام دلخواه + module.json با نام کانونی */
    private function makeZip(string $folderName, string $canonicalName): string
    {
        $sourceDir = $this->workspace.'/src/'.$folderName;

        if (! is_dir($sourceDir)) {
            mkdir($sourceDir, 0777, true);
        }
        file_put_contents(
            $sourceDir.'/module.json',
            json_encode(['name' => $canonicalName, 'alias' => strtolower($canonicalName)], JSON_UNESCAPED_UNICODE)
        );
        mkdir($sourceDir.'/app', 0777, true);
        file_put_contents($sourceDir.'/app/dummy.php', '<?php');

        $zipPath = $this->workspace.'/pkg.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile($sourceDir.'/module.json', $folderName.'/module.json');
        $zip->addFile($sourceDir.'/app/dummy.php', $folderName.'/app/dummy.php');
        $zip->close();

        return $zipPath;
    }

    public function test_zip_folder_lowercase_is_normalized_to_module_json_name(): void
    {
        $this->requireCaseSensitiveFilesystem();

        $zipPath = $this->makeZip('seoAudit', 'SeoAudit');

        $result = $this->extractZip->invoke($this->installer, $zipPath, 'seo-audit');

        $this->assertSame('SeoAudit', $result);
        $this->assertDirectoryExists($this->workspace.'/Modules/SeoAudit');
        $this->assertFileExists($this->workspace.'/Modules/SeoAudit/module.json');

        // پوشه با نام نادرست نباید ساخته شود
        $this->assertDirectoryDoesNotExist($this->workspace.'/Modules/seoAudit');
    }

    public function test_existing_legacy_differently_cased_directory_is_replaced(): void
    {
        $this->requireCaseSensitiveFilesystem();

        // نصب قدیمی با نام پوشه نادرست
        $legacy = $this->workspace.'/Modules/seoAudit';
        mkdir($legacy, 0777, true);
        file_put_contents($legacy.'/old-marker.txt', 'legacy');

        $zipPath = $this->makeZip('seoAudit', 'SeoAudit');

        $result = $this->extractZip->invoke($this->installer, $zipPath, 'seo-audit');

        $this->assertSame('SeoAudit', $result);
        $this->assertDirectoryExists($this->workspace.'/Modules/SeoAudit');
        $this->assertFileExists($this->workspace.'/Modules/SeoAudit/module.json');
        // پوشه قدیمی از مسیر فعال حذف و در .backups بایگانی شده است
        $this->assertDirectoryDoesNotExist($legacy);
        $this->assertDirectoryExists($this->workspace.'/Modules/.backups');
    }

    public function test_reinstall_over_existing_canonical_directory_is_backed_up(): void
    {
        $zipPath = $this->makeZip('SeoAudit', 'SeoAudit');

        $first = $this->extractZip->invoke($this->installer, $zipPath, 'seo-audit');
        $this->assertSame('SeoAudit', $first);

        // نصب مجدد — نسخه قبلی باید بایگانی شود نه اینکه حرکت/ادغام خراب شود
        $second = $this->extractZip->invoke($this->installer, $zipPath, 'seo-audit');
        $this->assertSame('SeoAudit', $second);
        $this->assertFileExists($this->workspace.'/Modules/SeoAudit/module.json');
        $this->assertDirectoryExists($this->workspace.'/Modules/.backups');
    }

    public function test_zip_without_module_json_is_rejected(): void
    {
        $zipPath = $this->workspace.'/broken.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('random/file.txt', 'no module.json here');
        $zip->close();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('module.json');

        $this->extractZip->invoke($this->installer, $zipPath, 'broken');
    }
}
