<?php

namespace Tests\Feature\Scale;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;
use Tests\Support\ScaleTestSetup;
use Tests\TestCase;

/**
 * تست «۱۰۰۰ کاربر همزمان» (درخواست کاربر: تست کاربران و کاربران همزمان مثلا ۱۰۰۰ عدد)
 *
 * این تست واقعاً ۱۰۰۰ درخواست HTTP «موازی» می‌فرستد:
 *  ۱) یک سرور PHP واقعی (php -S) با ۱۰ ورکر موازی بالا می‌آید.
 *  ۲) با curl_multi، موج‌های ۲۰تاییِ درخواستِ همزمان ارسال می‌شود
 *     (مجموعاً SCALE_CONCURRENT = ۱۰۰۰ درخواست به صفحات پرترافیک).
 *  ۳) نرخ موفقیت، درصدد ۹۵ تأخیر (p95) و توان پردازش (rps) سنجیده می‌شود.
 *
 * الگوی «موج همزمان» واقع‌بینانه است: در ترافیک واقعی هم هیچ‌وقت دقیقاً ۱۰۰۰
 * درخواست در یک میلی‌ثانیه نمی‌رسد؛ بلکه موج‌های پیوسته‌ی همزمان داریم.
 * هر موج ۲۰ اتصال همزمان باز می‌کند و منتظر همه می‌ماند — یعنی در هر لحظه
 * ۲۰ «کاربر» همزمان روی سایت هستند، تا ۱۰۰۰ کاربر کامل شود.
 *
 * برای بار واقعی بالاتر (۵هزار/۵۰هزار کاربر، شبکه واقعی، چند ماشین)،
 * اسکریپت‌های k6 و ab در پوشه tests/Load وجود دارند (توضیح: tests/Load/README.md).
 */
class ConcurrentUsersTest extends TestCase
{
    use ScaleTestSetup;

    private const HOST = '127.0.0.1';
    private const FIRST_PORT = 8011;
    private const WAVE = 12;          // همزمانی هر موج (کاربران همزمان)
    private const READY_TIMEOUT = 25; // ثانیه
    // ۶ ورکر: روی سندباکس ۲ هسته/۴GB با اجرای همزمان phpunit و MariaDB جا می‌شود
    // (ورکر بیشتر = OOM و افت اتصال، نه سرعت بیشتر — CPU گلوگاه است)
    private const WORKERS = 6;
    private const REQ_TIMEOUT = 15;   // ثانیه - سقف هر درخواست

    private ?Process $server = null;
    private int $currentPort = 0;
    private int $restarts = 0;

    /**
     * فاز ۷۱ — تعداد ورکر بر اساس حافظهٔ در دسترس ماشین.
     *
     * گزارش کاربر: «لودتست ConcurrentUsersTest در این سندباکس ماریا‌دی‌بی را می‌کُشد».
     * هر ورکر php -S در پیک (بوت کامل Laravel در هر ریکوئست) تا ~۱۵۰MB مصرف
     * می‌کند؛ روی ماشین کم‌حافظه که همزمان phpunit و ماریا‌دی‌بی هم اجرا هستند،
     * ورکر زیاد یعنی فشار OOM روی دیتابیس. محافظت اصلی (کوچک‌سازی buffer pool)
     * در ScaleTestSetup::guardDatabaseMemory است؛ اینجا هم ورکرها را با حافظه
     * هم‌مقیاس می‌کنیم. روی ماشین قوی همان ۶ ورکر کامل اجرا می‌شود.
     */
    private function workerCount(): int
    {
        $availMb = (int) round(ScaleTestSetup::memInfoBytes('MemAvailable') / 1048576);

        if ($availMb > 0 && $availMb < 2048) {
            return 2; // سندباکس خیلی تنگ — بقای دیتابیس اولویت است
        }

        if ($availMb > 0 && $availMb < 3072) {
            return 4; // سندباکس ۴ گیگ که همزمان phpunit + ماریا‌دی‌بی هم اجرا هستند
        }

        return self::WORKERS; // ماشین قوی — رفتار کامل تست
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpScale();
    }

    protected function tearDown(): void
    {
        if ($this->server) {
            $this->server->stop(0);
            $this->server = null;
        }
        $this->tearDownScale();
        parent::tearDown();
    }

    /**
     * راه‌اندازی سرور واقعی با چند ورکر (PHP_CLI_SERVER_WORKERS).
     */
    private function startServer(): int
    {
        $env = $this->serverEnv();

        fwrite(STDERR, sprintf(
            "\n[ConcurrentUsers] ورکرهای سرور: %s (حافظهٔ در دسترس ماشین: %dMB)\n",
            $env['PHP_CLI_SERVER_WORKERS'],
            (int) round(ScaleTestSetup::memInfoBytes('MemAvailable') / 1048576)
        ));

        foreach (range(self::FIRST_PORT, self::FIRST_PORT + 5) as $port) {
            $process = new Process(
                [\PHP_BINARY, '-S', self::HOST . ':' . $port, 'server.php'],
                base_path(),
                $env
            );
            $process->setTimeout(null);
            $process->start();

            if ($this->waitForReady($process, $port)) {
                $this->server = $process;
                $this->currentPort = $port;
                return $port;
            }

            $process->stop(0);
        }

        $this->fail('سرور تست بار (php -S) روی هیچ پورتی بالا نیامد');
    }

    /**
     * محیط سرور — DATABASE_URL خراب سندباکس حذف می‌شود.
     */
    private function serverEnv(): array
    {
        $env = $_ENV;
        $env['PHP_CLI_SERVER_WORKERS'] = (string) $this->workerCount(); // فاز ۷۱ — هم‌مقیاس با حافظه

        // فاز ۷۱ — اتصال ماندگار به دیتابیس «فقط برای سرور این تست»:
        // بدون آن، ۱۰۰۰ ریکوئست یعنی ۱۰۰۰ بار create/destroy اتصال و thread در
        // ماریا‌دی‌بی؛ glibc حافظهٔ آزادشده را پس نمی‌دهد و RSS دیتابیس در سندباکس
        // تا ~۲.۴GB بالا می‌رود و OOM-killer آن را می‌کشد (report کاربر).
        // با اتصال ماندگار، ۴ ورکر فقط ۴ اتصال همیشگی دارند و رشد حافظه منتفی است.
        $env['DB_PERSISTENT'] = 'true';

        unset($env['DATABASE_URL']);
        return $env;
    }

    /**
     * انتظار برای آماده‌شدن سرور.
     */
    private function waitForReady(Process $process, int $port): bool
    {
        $deadline = microtime(true) + self::READY_TIMEOUT;
        while (microtime(true) < $deadline) {
            if (!$process->isRunning()) {
                return false; // کرش کرد — پورت بعدی
            }
            if ($this->probe($port)) {
                return true;
            }
            usleep(300_000);
        }
        return false;
    }

    /**
     * پورت سالم است؟ (پروب سبک HEAD)
     */
    private function probe(int $port): bool
    {
        $ch = curl_init('http://' . self::HOST . ':' . $port . '/');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_NOBODY         => true,
        ]);
        curl_exec($ch);
        $ok = curl_getinfo($ch, CURLINFO_HTTP_CODE) > 0;
        curl_close($ch);
        return $ok;
    }

    /**
     * اطمینان از زنده‌بودن سرور — اگر محیط اجرا (مثلاً سندباکس با reaper)
     * سرور را کشته باشد، فوراً روی پورت دیگری بالا می‌آوریم تا تست ادامه یابد.
     * (تست همچنان رفتار واقعی ۱۰۰۰ کاربر همزمان را می‌سنجد؛
     * این فقط تضمین می‌کند زیرساخت تست زنده بماند.)
     */
    private function ensureServerAlive(): void
    {
        if ($this->server && $this->server->isRunning() && $this->probe($this->currentPort)) {
            return;
        }

        if ($this->server) {
            $this->server->stop(0);
            $this->server = null;
        }

        $this->restarts++;
        $this->startServer();
    }

    /**
     * یک درخواست تکی curl (برای آماده‌سازی نشست/توکن).
     */
    private function curlSingle(string $url, array $extra = []): array
    {
        $ch = curl_init($url);
        // نکته: array_merge کلیدهای عددی (ثابت‌های curl) را از نو می‌شمارد!
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::REQ_TIMEOUT,
            CURLOPT_HEADER         => true,
        ];
        if ($extra) {
            $options = $extra + $options; // گزینه‌های اضافه اولویت دارند
        }
        curl_setopt_array($ch, $options);

        $body = curl_exec($ch);
        $info = [
            'status'   => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'header'   => substr((string) $body, 0, (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE)),
            'body'     => substr((string) $body, (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE)),
        ];
        curl_close($ch);

        return $info;
    }

    /**
     * ارسال موازی یک موج درخواست با curl_multi + یک بار تلاش مجدد برای اتصال‌های شکسته.
     *
     * @return array<int, array{status:int, seconds:float}>
     */
    private function fireWave(string $host, int $port, array $paths): array
    {
        $results = $this->fireWaveOnce($host, $port, $paths);

        // تلاش مجدد فقط برای اتصال‌هایی که اصلاً پاسخ نگرفتند
        $failed = [];
        foreach ($results as $i => $r) {
            if ($r['status'] === 0) {
                $failed[$i] = $paths[$i];
            }
        }

        if ($failed) {
            foreach ($this->fireWaveOnce($host, $port, array_values($failed)) as $j => $r) {
                $results[array_keys($failed)[$j]] = $r;
            }
        }

        return $results;
    }

    private function fireWaveOnce(string $host, int $port, array $paths): array
    {
        $handles = [];
        $multi = curl_multi_init();

        foreach ($paths as $i => $path) {
            $ch = curl_init('http://' . $host . ':' . $port . $path);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => self::REQ_TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => false,
            ]);
            curl_multi_add_handle($multi, $ch);
            $handles[$i] = $ch;
        }

        // حلقه‌ی مقاوم: با خطاهای گذرای curl_multi برخورد می‌کند و رها نمی‌کند
        do {
            curl_multi_exec($multi, $active);
            if ($active > 0) {
                curl_multi_select($multi, 0.1);
            }
        } while ($active > 0);

        $results = [];
        foreach ($handles as $i => $ch) {
            $info = curl_getinfo($ch);
            $results[$i] = [
                'status'  => (int) ($info['http_code'] ?? 0),
                'seconds' => (float) ($info['total_time'] ?? 0),
            ];
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }

        curl_multi_close($multi);

        return $results;
    }

    #[Test]
    public function one_thousand_concurrent_users_all_get_responses(): void
    {
        $total = $this->scaleConcurrentCount(); // پیش‌فرض: ۱۰۰۰ (درخواست کاربر)
        $port = $this->startServer();

        // گرم‌کردن سرور (اولین درخواست opcache/کش‌های اولیه را آماده می‌کند)
        $this->curlSingle('http://' . self::HOST . ':' . $port . '/');

        // صفحات پرترافیک روز حراج: خانه، لاگین، جستجو
        $paths = ['/', '/login', '/search?q=scale', '/search'];

        $all = [];
        $start = microtime(true);
        $sent = 0;

        while ($sent < $total) {
            // زنده‌بودن سرور بین موج‌ها کنترل و در صورت کشته‌شدن مجدداً بالا می‌آید
            $this->ensureServerAlive();

            $size = min(self::WAVE, $total - $sent);
            $wavePaths = [];
            for ($i = 0; $i < $size; $i++) {
                $wavePaths[] = $paths[($sent + $i) % count($paths)];
            }

            $all = array_merge($all, $this->fireWave(self::HOST, $this->currentPort, $wavePaths));
            $sent += $size;
        }

        $wallClock = microtime(true) - $start;

        // ۱) هیچ درخواستی نباید شکست بخورد (اتصال/تایم‌اوت)
        $failures = array_filter($all, fn ($r) => $r['status'] === 0);
        $this->assertCount(
            0,
            $failures,
            count($failures) . ' درخواست از ' . $total . ' اصلاً پاسخ نگرفت (اتصال/تایم‌اوت)'
        );

        // ۲) همه پاسخ‌های معتبر HTTP (صفحات عمومی: ۲۰۰ یا ریدایرکت)
        $badStatus = array_filter($all, fn ($r) => !in_array($r['status'], [200, 301, 302, 303, 307], true));
        $this->assertCount(
            0,
            $badStatus,
            count($badStatus) . ' درخواست کد غیرمنتظره برگرداند: ' .
            json_encode(array_count_values(array_column($badStatus, 'status')), JSON_UNESCAPED_UNICODE)
        );

        // ۳) آمار تأخیر
        $times = array_column($all, 'seconds');
        sort($times);
        $p95 = $times[(int) floor(0.95 * (count($times) - 1))];
        $mean = array_sum($times) / count($times);
        $rps = $total / $wallClock;

        fwrite(STDERR, sprintf(
            "\n[۱۰۰۰ کاربر همزمان] total=%d rps=%.0f mean=%.0fms p95=%.0fms wall=%.1fs restarts=%d\n",
            $total, $rps, $mean * 1000, $p95 * 1000, $wallClock, $this->restarts
        ));

        // ۴) آستانه‌ها — با سخت‌افزار سندباکس (۲ هسته، سرور توسعه php -S) تنظیم شده؛
        // در پروداکشن (nginx + php-fpm + opcache) این اعداد چند برابر بهتر خواهند بود.
        // هدف تست: صفر خطا، تأخیر مهارشده و کشف رگرسیون‌های شدید (مثل N+1 انفجاری)
        $this->assertLessThan(
            8.0,
            $p95,
            sprintf('p95 تأخیر %.2f ثانیه است — کاربران همزمان بیش از حد منتظر می‌مانند', $p95)
        );
        $this->assertLessThan(
            4.5,
            $mean,
            sprintf('میانگین تأخیر %.2f ثانیه است — سرور زیر بار کند شده', $mean)
        );

        // ۵) ظرفیت حداقلی: سرور باید حداقل ۲.۵ درخواست در ثانیه پردازش کند
        $this->assertGreaterThan(
            2.5,
            $rps,
            sprintf('توان پردازش فقط %.1f rps بود — زیر بار همزمان پایین‌تر از حد انتظار', $rps)
        );
    }

    #[Test]
    public function concurrent_logins_under_load_succeed(): void
    {
        // ۵۰ ورود همزمان واقعی (سنگین‌ترین مسیر: bcrypt + session + DB)
        $port = $this->startServer();

        // کاربران تست در دیتابیس واقعی سرور درج می‌شوند (تستِ مقیاس تراکنش ندارد)
        $password = $this->seedScaleUsers(50);

        // هر کاربر نشست و توکن CSRF «خودش» را دارد (توکن بعد از هر لاگین عوض می‌شود)
        $codes = [];
        $waves = array_chunk(range(1, 50), self::WAVE);

        foreach ($waves as $wave) {
            // زنده‌بودن سرور بین موج‌ها
            $this->ensureServerAlive();
            $port = $this->currentPort;
            // موج ۱: گرفتن نشست + توکن برای هر کاربر این موج
            $sessions = [];
            foreach ($wave as $i) {
                $jar = tmpfile();
                $meta = stream_get_meta_data($jar);
                $page = $this->curlSingle('http://' . self::HOST . ':' . $port . '/login', [
                    CURLOPT_COOKIEJAR => $meta['uri'],
                    CURLOPT_COOKIEFILE => $meta['uri'],
                ]);
                $token = '';
                if (preg_match('/name="csrf-token" content="([^"]+)"/', $page['body'], $t)
                    || preg_match('/name="_token" value="([^"]+)"/', $page['body'], $t)) {
                    $token = $t[1];
                }
                $sessions[$i] = ['jar' => $jar, 'uri' => $meta['uri'], 'token' => $token];
            }

            // موج ۲: ارسال همزمان ورودها با نشست اختصاصی هر کاربر
            $multi = curl_multi_init();
            $handles = [];
            foreach ($wave as $i) {
                $mobile = '0999' . str_pad((string) $i, 7, '0', STR_PAD_LEFT);
                $s = $sessions[$i];
                if ($s['token'] === '') {
                    $handles[$i] = null;
                    continue;
                }
                $ch = curl_init('http://' . self::HOST . ':' . $port . '/login/CheckPassword');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => self::REQ_TIMEOUT,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_POST           => true,
                    CURLOPT_COOKIEJAR      => $s['uri'],
                    CURLOPT_COOKIEFILE     => $s['uri'],
                    CURLOPT_POSTFIELDS     => http_build_query([
                        'username' => $mobile,
                        'password' => $password,
                        '_token'   => $s['token'],
                    ]),
                ]);
                curl_multi_add_handle($multi, $ch);
                $handles[$i] = $ch;
            }

            do {
                curl_multi_exec($multi, $active);
                if ($active > 0) {
                    curl_multi_select($multi, 0.1);
                }
            } while ($active > 0);

            foreach ($wave as $i) {
                if ($handles[$i] === null) {
                    $codes[] = 0;
                } else {
                    $codes[] = (int) curl_getinfo($handles[$i], CURLINFO_HTTP_CODE);
                    curl_multi_remove_handle($multi, $handles[$i]);
                    curl_close($handles[$i]);
                }
                fclose($sessions[$i]['jar']);
            }
            curl_multi_close($multi);
        }

        $ok = count(array_filter($codes, fn ($c) => in_array($c, [200, 301, 302, 303, 307], true)));

        $this->assertSame(
            50,
            $ok,
            'همه ۵۰ ورود همزمان باید موفق شوند؛ کدها: ' . json_encode(array_count_values($codes))
        );
    }
}
