<?php

namespace App\Services;

class CaptchaImage
{
    private int $width = 240;

    private int $height = 72;

    private ?string $font = null;

    // پالت نئونی
    private const NEON = [
        [96, 165, 250], [52, 211, 153], [244, 114, 182],
        [251, 191, 36], [167, 139, 250], [56, 189, 248],
    ];

    public function __construct()
    {
        if (function_exists('imagettftext')) {
            foreach (['resources/fonts/captcha.ttf', 'public/fonts/captcha.ttf'] as $p) {
                if (is_file(base_path($p))) {
                    $this->font = base_path($p);
                    break;
                }
            }
        }
    }

    public function render(string $code): string
    {
        $im = $this->createBase();          // گرادیان تیره
        $this->drawDots($im);               // نویز نقطه‌ای
        $this->drawLines($im);              // خط/قوس/بیضی
        $this->drawCode($im, $code);        // حروف با هاله و چرخش
        $im = $this->wave($im);             // اعوجاج موجی

        ob_start();
        imagepng($im, null, 6);
        imagedestroy($im);

        return ob_get_clean();
    }

    private function createBase()
    {
        $im = imagecreatetruecolor($this->width, $this->height);
        [$r1, $g1, $b1] = [15, 23, 42];  // slate-900
        [$r2, $g2, $b2] = [30, 41, 59];  // slate-800

        for ($y = 0; $y < $this->height; $y++) {
            $t = $y / $this->height;
            $c = imagecolorallocate($im,
                (int) ($r1 + ($r2 - $r1) * $t),
                (int) ($g1 + ($g2 - $g1) * $t),
                (int) ($b1 + ($b2 - $b1) * $t));
            imageline($im, 0, $y, $this->width, $y, $c);
        }

        return $im;
    }

    private function drawDots($im): void
    {
        for ($i = 0; $i < 170; $i++) {
            $g = random_int(70, 160);
            $c = imagecolorallocatealpha($im, $g, $g, $g, random_int(45, 95));
            imagesetpixel($im, random_int(0, $this->width - 1), random_int(0, $this->height - 1), $c);
        }
    }

    private function drawLines($im): void
    {
        for ($i = 0; $i < 5; $i++) {
            $p = self::NEON[random_int(0, count(self::NEON) - 1)];
            $c = imagecolorallocatealpha($im, $p[0], $p[1], $p[2], random_int(65, 110));
            imagesetthickness($im, random_int(1, 2));

            match (random_int(0, 2)) {
                0 => imageline($im,
                    random_int(-15, $this->width), random_int(-10, $this->height),
                    random_int(-15, $this->width + 15), random_int(-10, $this->height + 10), $c),
                1 => imagearc($im,
                    random_int(-20, $this->width), random_int(-20, $this->height),
                    random_int(60, 200), random_int(40, 140),
                    random_int(0, 360), random_int(0, 360), $c),
                default => imageellipse($im,
                    random_int(20, $this->width - 20), random_int(10, $this->height - 10),
                    random_int(30, 90), random_int(20, 60), $c),
            };
        }
        imagesetthickness($im, 1);
    }

    private function drawCode($im, string $code): void
    {
        $len = mb_strlen($code);
        // فاز ۲۸: حاشیه بیشتر در ابتدا/انتها تا حرف اول و آخر با چرخش/موج بریده نشوند
        // فاز ۱۲۱: بوم ۲۴۰×۷۲ و فونت درشت‌تر (۲۷–۳۳) — خوانایی و جا شدن ۵ کاراکتر
        $slot = ($this->width - 36) / $len;

        if ($this->font) {
            foreach (mb_str_split($code) as $i => $ch) {
                $color = self::NEON[random_int(0, count(self::NEON) - 1)];
                $solid = imagecolorallocate($im, $color[0], $color[1], $color[2]);
                $glow = imagecolorallocatealpha($im, $color[0], $color[1], $color[2], 72);

                $size = random_int(27, 33);
                $angle = random_int(-13, 13);
                $x = (int) (16 + $slot * $i + random_int(-2, 4));
                $y = (int) ($this->height / 2 + $size / 2.7 + random_int(-7, 7));

                // هاله (دو بار نیمه‌شفاف) + متن اصلی
                imagettftext($im, $size, $angle, $x, $y, $glow, $this->font, $ch);
                imagettftext($im, $size, $angle, $x, $y, $glow, $this->font, $ch);
                imagettftext($im, $size, $angle, $x, $y, $solid, $this->font, $ch);
            }

            return;
        }

        // ---------- fallback بدون TTF: بوم کوچک + بزرگ‌نمایی نرم ----------
        $sh = 40;
        $small = imagecreatetruecolor($this->width, $sh);
        imagefill($small, 0, 0, imagecolorallocate($small, 22, 31, 51));

        foreach (mb_str_split($code) as $i => $ch) {
            $p = self::NEON[random_int(0, count(self::NEON) - 1)];
            imagestring($small, 5, (int) (16 + $slot * $i), random_int(3, 14), $ch,
                imagecolorallocate($small, $p[0], $p[1], $p[2]));
        }

        $dh = 64;
        imagecopyresampled($im, $small, 0, (int) (($this->height - $dh) / 2), 0, 0,
            $this->width, $dh, $this->width, $sh);
        imagedestroy($small);
    }

    private function wave($im)
    {
        $out = imagecreatetruecolor($this->width, $this->height);
        imagecopy($out, $im, 0, 0, 0, 0, $this->width, $this->height);

        for ($x = 0; $x < $this->width; $x++) {
            $dy = (int) round(sin($x * 0.075) * 3 + sin($x * 0.02 + 1.3) * 2.5);
            $srcY = max(0, $dy);
            $dstY = max(0, -$dy);
            imagecopy($out, $im, $x, $dstY, $x, $srcY, 1, $this->height - abs($dy));
        }
        imagedestroy($im);

        return $out;
    }
}
