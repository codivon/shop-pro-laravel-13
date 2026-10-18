<?php

namespace App\Services\AdminPanel;

use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Support\Facades\Log;

/**
 * دریافت محصول از دیجی‌کالا — پورت getProductFromSite/digikala پنل قدیمی
 *
 * به‌جای خروجی HTML (پنل قدیمی)، داده ساختاریافته برمی‌گرداند تا فرم Livewire
 * مستقیماً فیلدها را پر کند. تصاویر در public/uploads/tmp ذخیره می‌شوند
 * (مطابق رفتار قدیمی) و در زمان ذخیره محصول بهینه‌سازی می‌شوند.
 */
class ProductScraper
{
    /**
     * دریافت محصول از API دیجی‌کالا
     *
     * خروجی:
     *  error, title, price, variantRows[]  {attributes: [id], price}
     *  galleryFiles[] (نام فایل‌ها در public/uploads/tmp), baseImage
     *  categoryId, brandName, weight, descriptionHtml,
     *  specGroups[] {name, specifications[] {name, value, special}},
     *  metaTitle, metaDescription
     */
    public function fetchDigikala(string $siteCode, bool $autoCreateCategory = true): array
    {
        try {
            $result = $this->callApi('https://api.digikala.com/v2/product/' . $siteCode . '/');

            $images = [];
            $image = null;
            $weight = null;

            if (! isset($result['data']['product']['default_variant']['price']['selling_price'])) {
                throw new \Exception('قیمت محصول یافت نشد');
            }

            // تبدیل ریال به تومان (حذف رقم آخر)
            $defaultPrice = substr($result['data']['product']['default_variant']['price']['selling_price'], 0, -1);

            if (! is_dir('uploads/tmp')) {
                mkdir('uploads/tmp', 0777, true);
            }

            // ==================== دسته‌بندی ====================
            $category = null;
            if ($autoCreateCategory && isset($result['data']['product']['category'])) {
                $categoryData = $result['data']['product']['category'];

                $category = Category::where('title', $categoryData['title_fa'])->first();

                if (! $category) {
                    $category = Category::create([
                        'title' => $categoryData['title_fa'],
                        'slug' => sluggable_helper_function($categoryData['title_fa']),
                        'type' => 'productcat',
                        'lang' => 'fa',
                    ]);
                }
            }

            // ==================== تصویر اصلی ====================
            if (isset($result['data']['product']['images']['main']['webp_url'][0])) {
                $imageUrlBase = $result['data']['product']['images']['main']['webp_url'][0];
                $imageName = $this->baseName($imageUrlBase);
                $path = 'uploads/tmp/' . $imageName;

                if (! file_exists($path)) {
                    $this->resizeAndSaveSiteImage($imageUrlBase, $path, 600, 600);
                }

                $image = $imageName;
            }

            // ==================== لیست تصاویر ====================
            if (isset($result['data']['product']['images']['list']) && count($result['data']['product']['images']['list'])) {
                $counter = 0;
                foreach ($result['data']['product']['images']['list'] as $item) {
                    if ($counter >= 11) {
                        break;
                    }

                    if (isset($item['url'][0])) {
                        $imageUrlBase = $item['url'][0];
                        $imageName = $this->baseName($imageUrlBase);
                        $path = 'uploads/tmp/' . $imageName;

                        if (! file_exists($path)) {
                            $this->resizeAndSaveSiteImage($imageUrlBase, $path);
                            usleep(500000);
                        }

                        $images[] = $imageName;
                        $counter++;
                    }
                }
            }

            // ==================== قیمت هر رنگ ====================
            $variants = $result['data']['product']['variants'] ?? [];
            $colorPrices = [];

            foreach ($variants as $variant) {
                if (isset($variant['color']) && isset($variant['price']['selling_price'])) {
                    $colorId = $variant['color']['id'];
                    $price = substr($variant['price']['selling_price'], 0, -1);

                    // چند واریانت برای یک رنگ → ارزان‌ترین
                    if (! isset($colorPrices[$colorId]) || $price < $colorPrices[$colorId]['price']) {
                        $colorPrices[$colorId] = [
                            'title' => $variant['color']['title'],
                            'hex_code' => $variant['color']['hex_code'],
                            'price' => $price,
                        ];
                    }
                }
            }

            // ==================== گروه رنگ (در صورت نبود ساخته می‌شود) ====================
            $attributeGroup = AttributeGroup::where('type', 'color')->first();
            if (! $attributeGroup) {
                $attributeGroup = AttributeGroup::create([
                    'type' => 'color',
                    'name' => 'رنگ',
                ]);
            }

            // ==================== ردیف‌های تنوع بر اساس رنگ ====================
            $variantRows = [];

            if (isset($result['data']['product']['colors']) && count($result['data']['product']['colors'])) {
                foreach ($result['data']['product']['colors'] as $color) {
                    $colorPrice = $colorPrices[$color['id']]['price'] ?? $defaultPrice;

                    $attribute = Attribute::where([
                        'attribute_group_id' => $attributeGroup->id,
                        'name' => $color['title'],
                    ])->first();

                    if (! $attribute) {
                        $attribute = Attribute::create([
                            'attribute_group_id' => $attributeGroup->id,
                            'name' => $color['title'],
                            'value' => $color['hex_code'] ?? null,
                        ]);
                    }

                    $variantRows[] = [
                        'attributes' => [$attribute->id],
                        'price' => (int) $colorPrice,
                    ];
                }
            } else {
                $variantRows[] = [
                    'attributes' => [],
                    'price' => (int) $defaultPrice,
                ];
            }

            // ==================== وزن ====================
            if (isset($result['data']['product']['specifications'][0]['attributes'])) {
                $getWeights = collect($result['data']['product']['specifications'][0]['attributes'])
                    ->where('title', 'وزن')
                    ->first();

                if ($getWeights && isset($getWeights['values'][0])) {
                    $weights = explode('گرم', $getWeights['values'][0]);
                    if (count($weights)) {
                        $weights = $weights[0];
                        $weight = explode('کیلو', $weights);
                        if (count($weight) > 1) {
                            $weight = trim($weight[0]) * 1000;
                        } else {
                            $weight = trim($weight[0]);
                        }
                    }
                }
            }

            // ==================== برند ====================
            $brandName = null;
            if (isset($result['data']['product']['brand'])) {
                $brand1 = Brand::where('name', $result['data']['product']['brand']['code'])->first();

                if ($brand1) {
                    $brandName = $brand1->name;
                } elseif (isset($result['data']['product']['brand']['logo']['url'][0])) {
                    $imageUrlBase = $result['data']['product']['brand']['logo']['url'][0];
                    $imageName = $this->baseName($imageUrlBase);

                    if (! is_dir('uploads/brands')) {
                        mkdir('uploads/brands', 0777, true);
                    }

                    $path = 'uploads/brands/' . $imageName;

                    if (file_exists($path)) {
                        unlink($path);
                    }

                    $this->resizeAndSaveSiteImage($imageUrlBase, $path, 500, 500);

                    $brand = Brand::create([
                        'name' => $result['data']['product']['brand']['code'],
                        'name_en' => $result['data']['product']['brand']['title_en'] ?? null,
                        'image' => $path,
                        'slug' => $result['data']['product']['brand']['code'],
                    ]);

                    $brandName = $brand->name;
                }
            }

            // ==================== نظرات کارشناسی (توضیحات) ====================
            $review = [];
            if (isset($result['data']['product']['expert_reviews']['review_sections'])) {
                foreach ($result['data']['product']['expert_reviews']['review_sections'] as $reviews) {
                    if (isset($reviews['sections'])) {
                        foreach ($reviews['sections'] as $sections) {
                            if (isset($sections['template'])) {
                                if ($sections['template'] == 'text' && isset($sections['text'])) {
                                    $title = '<p style="font-weight: 700;font-size: 1.6rem;line-height: 2.15;color: #080a38;">' . ($reviews['title'] ?? '') . '</p>';
                                    $review[] = $title . $sections['text'];
                                }

                                if ($sections['template'] == 'image' && isset($sections['image'])) {
                                    $review[] = '<img style="width: 100%;" src="' . $sections['image'] . '">';
                                }
                            }
                        }
                    }
                }
            }

            // ==================== مشخصات فنی ====================
            $specGroups = [];
            if (isset($result['data']['product']['specifications']) && count($result['data']['product']['specifications'])) {
                foreach ($result['data']['product']['specifications'] as $specification) {
                    $group = [
                        'name' => $specification['title'] ?? ($result['data']['product']['category']['title_fa'] ?? 'مشخصات'),
                        'specifications' => [],
                    ];

                    foreach (($specification['attributes'] ?? []) as $attr) {
                        $group['specifications'][] = [
                            'name' => $attr['title'] ?? '',
                            'value' => $attr['values'][0] ?? '',
                            'special' => false,
                        ];
                    }

                    if ($group['specifications']) {
                        $specGroups[] = $group;
                    }
                }
            }

            // ==================== سئو ====================
            $metaTitle = null;
            $metaDescription = null;
            if (isset($result['data']['seo'])) {
                $seo = $result['data']['seo'];
                $metaTitle = $seo['title'] ?? null;
                $metaDescription = $seo['description'] ?? null;
            }

            $weightValue = 0;
            if (! empty($weight)) {
                if (is_array($weight)) {
                    $weightValue = trim(round((float) reset($weight)));
                } elseif (is_string($weight) && (str_contains($weight, ',') || str_contains($weight, ' '))) {
                    $firstNumber = explode(',', $weight)[0];
                    $firstNumber = explode(' ', $firstNumber)[0];
                    $weightValue = trim(round((float) $firstNumber));
                } else {
                    $weightValue = trim(round((float) $weight));
                }
            }

            return [
                'error' => null,
                'title' => $result['data']['product']['title_fa'] ?? null,
                'price' => (int) $defaultPrice,
                'variantRows' => $variantRows,
                'galleryFiles' => $images,
                'baseImage' => $image,
                'categoryId' => $category?->id,
                'brandName' => $brandName,
                'weight' => (int) $weightValue,
                'descriptionHtml' => implode('', $review),
                'specGroups' => $specGroups,
                'metaTitle' => $metaTitle,
                'metaDescription' => $metaDescription,
            ];
        } catch (\Exception $e) {
            Log::error('Digikala API Error: ' . $e->getMessage(), [
                'siteCode' => $siteCode,
            ]);

            return [
                'error' => $e->getMessage(),
                'title' => null,
                'price' => 0,
                'variantRows' => [],
                'galleryFiles' => [],
                'baseImage' => null,
                'categoryId' => null,
                'brandName' => null,
                'weight' => 0,
                'descriptionHtml' => null,
                'specGroups' => [],
                'metaTitle' => null,
                'metaDescription' => null,
            ];
        }
    }

    /**
     * دریافت و رزایز تصویر — پورت resizeAndSaveSiteImage
     */
    public function resizeAndSaveSiteImage($imageUrl, $savePath, $width = null, $height = null)
    {
        $ch = curl_init($imageUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $imageContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $imageContent === false) {
            return false;
        }

        // اگر سایز مشخص نشده
        if ($width === null && $height === null) {
            return file_put_contents($savePath, $imageContent) !== false;
        }

        // ذخیره موقت و تغییر سایز
        $tempPath = $savePath . '.tmp';
        file_put_contents($tempPath, $imageContent);

        $imageInfo = @getimagesize($tempPath);
        if ($imageInfo === false) {
            @unlink($tempPath);

            return false;
        }

        $mime = $imageInfo['mime'];
        switch ($mime) {
            case 'image/jpeg':
                $sourceImage = @imagecreatefromjpeg($tempPath);
                break;
            case 'image/png':
                $sourceImage = @imagecreatefrompng($tempPath);
                break;
            case 'image/webp':
                $sourceImage = @imagecreatefromwebp($tempPath);
                break;
            default:
                @unlink($tempPath);

                return false;
        }

        if (! $sourceImage) {
            @unlink($tempPath);

            return false;
        }

        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        if ($width && $height) {
            $ratio = min($width / $originalWidth, $height / $originalHeight);
            $newWidth = round($originalWidth * $ratio);
            $newHeight = round($originalHeight * $ratio);
        } elseif ($width) {
            $newWidth = $width;
            $newHeight = round($originalHeight * ($width / $originalWidth));
        } elseif ($height) {
            $newHeight = $height;
            $newWidth = round($originalWidth * ($height / $originalHeight));
        } else {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        if ($mime == 'image/png') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($resizedImage, $savePath, 90);
                break;
            case 'image/png':
                imagepng($resizedImage, $savePath, 9);
                break;
            case 'image/webp':
                imagewebp($resizedImage, $savePath, 90);
                break;
        }

        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
        @unlink($tempPath);

        return true;
    }

    /**
     * نام فایل از URL تصویر دیجی‌کالا (حذف query string)
     */
    protected function baseName(string $url): string
    {
        if (strpos($url, '?') !== false) {
            $t = explode('?', $url);

            return basename($t[0]);
        }

        return basename($url);
    }

    /**
     * فراخوانی API دیجی‌کالا
     */
    protected function callApi(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Accept-Charset: utf-8',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('Curl error: ' . $error);
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('API returned HTTP code: ' . $httpCode);
        }

        // حذف BOM
        if (substr($result, 0, 3) === "\xEF\xBB\xBF") {
            $result = substr($result, 3);
        }

        $decoded = json_decode($result, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg());
        }

        return $decoded;
    }
}
