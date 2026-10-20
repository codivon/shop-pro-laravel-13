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

    /* ============================ ترب (فاز ۲۱/۲۳/۲۴) ============================ */

    /**
     * دریافت محصول از ترب — بازنویسی کامل در فاز ۲۳، تکمیل در فاز ۲۴
     *
     * دلایل «هیچ چیز نمی‌گیرد» در نسخه‌ی قبلی:
     *  ۱) پاسخ خطای API (مثل {"status":404} یا {"message":"..."}) چون کلید detail
     *     نداشت، «پاسخ سالم» فرض می‌شد → هیچ فیلدی پر نمی‌شد و فرم با پیام موفقیت
     *     خالی می‌ماند (silent-empty)؛
     *  ۲) ساختار واقعی ترب (Next.js) در __NEXT_DATA__ است که اصلا پارس نمی‌شد؛
     *  ۳) URL تصاویر نسبی (//gt.torob.com/...) رد می‌شد؛
     *  ۴) هدرهای callApi شبیه مرورگر نبود → ترب 403/490 می‌داد.
     *
     * فاز ۲۴ — «اطلاعات ناقص»: پیلود جدید ترب (verified با API زنده):
     *  - اندپوینت جدید /v4/base-product/details/ (جمع!) به‌جای detail
     *  - تصاویر: image_url + image_count؛ گالری از کل پیلود جمع‌آوری می‌شود
     *    (پیمایش بازگشتی همه‌ی کلیدهای image_url/image1/image2/thumb_url...)
     *  - «تنوع قیمت بر اساس اینچ/سایز» ← آرایه variants[] (تب‌های تنوع؛ هر آیتم
     *    یک محصول همان خانواده با قیمت خودش) → ردیف‌های تنوع + گروه ویژگی
     *  - «ویژگی‌ها» ← key_specs[] و structural_specs.headers[] → specGroups
     *  - دسته‌بندی ← breadcrumbs (آخرین لینک)
     *
     * استراتژی چندلایه‌ای:
     *  ۱) API ترب — اندپوینت details (جمع) و detail (قدیمی) + اعتبارسنجی محتوا
     *  ۲) API جستجوی ترب با نام محصول (از اسلاگ URL) → more_info_url → details
     *  ۳) HTML صفحه → پارس __NEXT_DATA__ (گردشگر بازگشتی به دنبال گره محصول)
     *  ۴) HTML صفحه → پارس JSON-LD (schema.org Product)
     *  ۵) اگر هیچ لایه‌ای داده نداد → خطای توضیحی با وضعیت هر لایه (هرگز silent!)
     */
    public function fetchTorob(string $urlOrCode): array
    {
        $empty = [
            'error' => null,
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

        try {
            $prk = $this->extractTorobKey($urlOrCode);

            if (! $prk) {
                throw new \Exception('آدرس صفحه محصول ترب یا کد آن معتبر نیست. مثال: https://torob.com/p/abc123/نام-محصول/');
            }

            if (! is_dir('uploads/tmp')) {
                mkdir('uploads/tmp', 0775, true);
            }

            // ---------- ۱) API ترب (اندپوینت جدید details + قدیمی detail) ----------
            $product = null;
            $attempts = [];

            $endpoints = [
                "https://api.torob.com/v4/base-product/details/?source=next_desktop&prk={$prk}",
                "https://api.torob.com/v4/base-product/details/?prk={$prk}",
                "https://api.torob.com/v4/base-product/detail/?source=next_desktop&prk={$prk}",
                "https://api.torob.com/v4/base-product/detail/?prk={$prk}",
                "https://api.torob.com/v2/base-product/detail/?prk={$prk}",
            ];

            foreach ($endpoints as $endpoint) {
                try {
                    $candidate = $this->callApi($endpoint);

                    if ($this->isTorobProductPayload($candidate)) {
                        $product = $candidate;

                        break;
                    }

                    $attempts['api'] = 'پاسخ نامعتبر: ' . mb_substr(json_encode($candidate, JSON_UNESCAPED_UNICODE) ?: '', 0, 120);
                } catch (\Throwable $e) {
                    $attempts['api'] = $e->getMessage();
                }
            }

            // ---------- ۲) جستجوی ترب با نام محصول (از اسلاگ آدرس) ----------
            if (! $product) {
                $product = $this->torobSearchFallback($urlOrCode, $prk, $attempts);
            }

            // ---------- ۳ و ۴) صفحه HTML → __NEXT_DATA__ / JSON-LD ----------
            $nextData = null;
            $jsonLd = null;

            if (! $product) {
                $pageUrl = str_contains($urlOrCode, 'torob.com') ? $urlOrCode : "https://torob.com/p/{$prk}/";
                [$pageHtml, $fetchError] = $this->fetchTorobPage($pageUrl);
                $attempts['page'] = $fetchError ?: 'ok';

                if ($pageHtml) {
                    $nextData = $this->parseTorobNextData($pageHtml);

                    if ($this->isTorobProductPayload($nextData)) {
                        $product = $nextData;
                    }

                    if (! $product) {
                        $jsonLd = $this->parseTorobJsonLd($pageHtml);
                    }
                }
            }

            if (! $product) {
                $blocked = str_contains((string) ($attempts['api'] ?? ''), '403')
                    || str_contains((string) ($attempts['api'] ?? ''), '490')
                    || str_contains((string) ($attempts['page'] ?? ''), '403')
                    || str_contains((string) ($attempts['page'] ?? ''), '490');

                $hint = $blocked
                    ? ' — ترب دسترسی سرورها/ربات‌ها را مسدود کرده است (HTTP 403/490). لطفا آدرس را در مرورگر باز و از دیجی‌کالا استفاده کنید یا چند دقیقه بعد تلاش کنید.'
                    : '';

                $detail = implode(' | ', array_map(fn ($k, $v) => "{$k}: {$v}", array_keys($attempts), $attempts));

                Log::error('Torob fetch failed', ['input' => $urlOrCode, 'attempts' => $attempts]);

                throw new \Exception('دریافت اطلاعات از ترب ناموفق بود' . $hint . ($detail ? ' [' . $detail . ']' : ''));
            }

            // ---------- نرمال‌سازی ساختار (تو در تویی API) ----------
            foreach (['product', 'data', 'base_product'] as $wrapKey) {
                if (isset($product[$wrapKey]) && is_array($product[$wrapKey])
                    && ! isset($product['title1']) && ! isset($product['name_fa'])) {
                    $product = $product[$wrapKey] + $product;
                }
            }

            $mapped = $this->mapTorobProduct($product, $jsonLd);

            // ---------- ۴) هرگز silent-empty برنگردان ----------
            if (empty($mapped['title']) && empty($mapped['price']) && empty($mapped['baseImage'])) {
                Log::error('Torob fetch: payload accepted but nothing extracted', ['keys' => array_keys($product)]);

                throw new \Exception('پاسخ ترب دریافت شد اما داده‌ی قابل استفاده‌ای در آن نبود. لطفا آدرس دقیق صفحه‌ی محصول را وارد کنید.');
            }

            return array_merge($empty, $mapped);
        } catch (\Exception $e) {
            Log::error('Torob fetch error: ' . $e->getMessage(), ['input' => $urlOrCode]);

            return array_merge($empty, ['error' => $e->getMessage()]);
        }
    }

    /**
     * آیا این پاسخ، یک محصول واقعی ترب است؟ (جلوگیری از silent-empty)
     */
    protected function isTorobProductPayload($payload): bool
    {
        if (! is_array($payload) || empty($payload)) {
            return false;
        }

        // خطاهای متداول API — هر شکلی که باشند
        foreach (['detail', 'message', 'error', 'status_code'] as $errKey) {
            if (isset($payload[$errKey]) && ! is_array($payload[$errKey])) {
                $val = strtolower(trim((string) $payload[$errKey]));

                if ($val !== '' && $val !== 'ok' && ! is_numeric($val)) {
                    return false;
                }
            }
        }

        $hasTitle = ! empty($payload['title1']) || ! empty($payload['title2'])
            || ! empty($payload['name_fa']) || ! empty($payload['name1']);

        $hasPrice = isset($payload['min_price']) || isset($payload['price']) || isset($payload['lowest_price']);
        $hasImage = ! empty($payload['image1']) || ! empty($payload['image_url']);
        $hasOffers = ! empty($payload['results']) && is_array($payload['results']);

        return $hasTitle && ($hasPrice || $hasImage || $hasOffers);
    }

    /**
     * نگاشت گره محصول ترب به خروجی استاندارد فرم
     */
    protected function mapTorobProduct(array $product, ?array $jsonLd): array
    {
        $pick = function (array $keys, $default = null) use ($product) {
            foreach ($keys as $key) {
                if (isset($product[$key]) && $product[$key] !== '' && $product[$key] !== null) {
                    return $product[$key];
                }
            }

            return $default;
        };

        // ---------- عنوان ----------
        $title = $pick(['title1', 'title2', 'name1', 'name_fa', 'title', 'name']);
        if (! $title && $jsonLd) {
            $title = $jsonLd['title'] ?? null;
        }

        // ---------- قیمت (تومان) ----------
        $price = (int) preg_replace('/[^\d]/', '', (string) $pick(['min_price', 'price', 'lowest_price', 'price_toman'], 0));
        if (! $price && ! empty($jsonLd['price'])) {
            $price = (int) preg_replace('/[^\d]/', '', (string) $jsonLd['price']);
        }

        if (! $price) {
            // پیلود جدید گاهی قیمت را فقط در price_text فارسی می‌فرستد (۹۵٫۸۰۰٫۰۰۰)
            $price = $this->torobToman($product['price_text'] ?? $product['price_string'] ?? null);
        }

        // ---------- تصاویر ----------
        // گالری از کل پیلود جمع‌آوری می‌شود: کلیدهای اصلی + پیمایش بازگشتی
        // (products_info.result / results / variants / more_info و هر شکل دیگر)
        $imageUrls = [];

        foreach (['image_url', 'image1', 'image2', 'image', 'main_image', 'thumb_url'] as $key) {
            $val = $product[$key] ?? null;
            $this->collectTorobImageUrl($val, $imageUrls);
        }

        $this->collectTorobImagesRecursive($product, $imageUrls, 0);

        if (! $imageUrls && ! empty($jsonLd['image'])) {
            foreach ((array) $jsonLd['image'] as $img) {
                $this->collectTorobImageUrl($img, $imageUrls);
            }
        }

        $imageUrls = array_values(array_unique($imageUrls));

        $baseImage = null;
        $galleryFiles = [];

        foreach ($imageUrls as $i => $imgUrl) {
            if ($i >= 11) {
                break;
            }

            $imageName = $this->baseName(parse_url($imgUrl, PHP_URL_PATH) ?: $imgUrl);

            if (! preg_match('/\.(jpe?g|png|webp)$/i', $imageName)) {
                $imageName .= '.jpg';
            }

            $path = 'uploads/tmp/torob-' . $imageName;

            if (! file_exists($path)) {
                $ok = $i === 0
                    ? $this->resizeAndSaveSiteImage($imgUrl, $path, 600, 600)
                    : $this->resizeAndSaveSiteImage($imgUrl, $path);

                if (! $ok) {
                    continue;
                }

                if ($i > 0) {
                    usleep(400000);
                }
            }

            if ($i === 0) {
                $baseImage = 'torob-' . $imageName;
            } else {
                $galleryFiles[] = 'torob-' . $imageName;
            }
        }

        // ---------- دسته‌بندی ----------
        $categoryId = null;
        $categoryName = null;

        // فاز ۲۴: مسیر دسته در پیلود جدید در breadcrumbs است (آخرین لینک = دسته برگ)
        if (! empty($product['breadcrumbs']) && is_array($product['breadcrumbs'])) {
            foreach (array_reverse($product['breadcrumbs']) as $crumb) {
                $crumbTitle = is_array($crumb) ? ($crumb['title'] ?? null) : null;
                $crumbId = is_array($crumb) ? ($crumb['cat_id'] ?? $crumb['id'] ?? null) : null;

                if ($crumbTitle && (string) $crumbId !== '0' && (string) $crumbId !== '') {
                    $categoryName = $crumbTitle;

                    break;
                }
            }
        }

        if (! $categoryName) {
            $categoryName = $pick(['category_name', 'category_title']);
        }

        if (! $categoryName && isset($product['torob_category']) && is_array($product['torob_category'])) {
            $categoryName = $product['torob_category']['title'] ?? $product['torob_category']['name'] ?? null;
        }

        if (! $categoryName && isset($product['category']) && is_array($product['category'])) {
            $categoryName = $product['category']['title'] ?? $product['category']['name'] ?? null;
        }

        if (! $categoryName && ! empty($jsonLd['category'])) {
            $categoryName = is_array($jsonLd['category']) ? end($jsonLd['category']) : $jsonLd['category'];
        }

        if ($categoryName) {
            $category = Category::where('title', trim((string) $categoryName))->where('type', 'productcat')->first();

            if (! $category) {
                $category = Category::create([
                    'title' => trim((string) $categoryName),
                    'slug' => sluggable_helper_function(trim((string) $categoryName)),
                    'type' => 'productcat',
                    'lang' => 'fa',
                ]);
            }

            $categoryId = $category->id;
        }

        // ---------- مشخصات فنی (ویژگی‌ها) ----------
        // فاز ۲۴: key_specs + structural_specs (پیلود جدید) + شکل‌های قدیمی
        $specGroups = $this->torobSpecGroups($product, $title);

        // ---------- تنوع قیمت (مثلا بر اساس اینچ/سایز) ----------
        // فاز ۲۴: آرایه variants[] ترب (تب‌های تنوع) → ردیف‌های تنوع فرم
        $variantRows = $this->torobVariantRows($product);

        if (! $variantRows) {
            $variantRows = $price ? [['attributes' => [], 'price' => $price]] : [];
        }

        // ---------- توضیحات ----------
        $description = $pick(['description', 'description_text']);
        if (! $description && ! empty($jsonLd['description'])) {
            $description = $jsonLd['description'];
        }

        // ---------- برند ----------
        $brandName = $pick(['brand_name', 'brand']);

        if (is_array($brandName)) {
            $brandName = $brandName['name'] ?? $brandName['title'] ?? null;
        }

        if (! $brandName && ! empty($jsonLd['brand'])) {
            $brand = $jsonLd['brand'];
            $brandName = is_array($brand) ? ($brand['name'] ?? null) : $brand;
        }

        return [
            'title' => $title ? (string) $title : null,
            'price' => $price,
            'variantRows' => $variantRows,
            'galleryFiles' => $galleryFiles,
            'baseImage' => $baseImage,
            'categoryId' => $categoryId,
            'brandName' => $brandName ? (string) $brandName : null,
            'descriptionHtml' => $description ? '<p>' . e((string) $description) . '</p>' : null,
            'specGroups' => $specGroups,
            'metaTitle' => $title ? (string) $title : null,
            'metaDescription' => $description ? mb_substr(strip_tags((string) $description), 0, 300) : null,
        ];
    }

    /**
     * فاز ۲۴ — لایه‌ی پشتیبان: جستجوی ترب با نام محصول (از اسلاگ آدرس)
     *
     * اندپوینت details گاهی به search_id نیاز دارد که فقط در ردیف‌های جستجو
     * فرستاده می‌شود؛ اسلاگ آدرس torob.com/p/{prk}/{نام-محصول}/ را به کوئری
     * جستجو تبدیل می‌کنیم و از ردیفِ همین prk، لینک آماده‌ی more_info_url
     * (شامل prk + search_id) را باز می‌کنیم.
     */
    protected function torobSearchFallback(string $urlOrCode, string $prk, array &$attempts): ?array
    {
        $query = null;

        if (preg_match('~torob\.com/p/[A-Za-z0-9-]+/([^/?#]+)/?~iu', $urlOrCode, $m)) {
            $query = rawurldecode($m[1]);
            $query = trim(str_replace(['-', '_', '+'], ' ', $query));
        }

        if (! $query || mb_strlen($query) < 3) {
            return null;
        }

        try {
            $search = $this->callApi('https://api.torob.com/v4/base-product/search/?q=' . rawurlencode($query) . '&size=12&sort=popularity');
            $rows = $search['results'] ?? [];

            if (! is_array($rows)) {
                $attempts['search'] = 'بدون نتیجه';

                return null;
            }

            foreach ($rows as $row) {
                if (! is_array($row) || strcasecmp((string) ($row['random_key'] ?? ''), $prk) !== 0) {
                    continue;
                }

                $moreUrl = $row['more_info_url'] ?? null;

                if (! is_string($moreUrl) || ! str_contains($moreUrl, 'api.torob.com')) {
                    $attempts['search'] = 'ردیف پیدا شد اما more_info_url ندارد';

                    return null;
                }

                try {
                    $candidate = $this->callApi($moreUrl);

                    if ($this->isTorobProductPayload($candidate)) {
                        return $candidate;
                    }

                    $attempts['search'] = 'پاسخ details نامعتبر بود';
                } catch (\Throwable $e) {
                    $attempts['search'] = $e->getMessage();
                }

                return null;
            }

            $attempts['search'] = 'محصول در نتایج جستجو پیدا نشد';
        } catch (\Throwable $e) {
            $attempts['search'] = $e->getMessage();
        }

        return null;
    }

    /**
     * فاز ۲۴ — پیمایش بازگشتی کل پیلود برای جمع‌آوری همه‌ی تصاویر گالری
     *
     * گالری در شکل‌های مختلف می‌آید (image_url/image1/image2/thumb_url/...
     * در products_info.result یا results یا variants یا more_info) — همه جمع می‌شود.
     */
    protected function collectTorobImagesRecursive($node, array &$out, int $depth): void
    {
        if ($depth > 14 || count($out) >= 14) {
            return;
        }

        if (is_array($node)) {
            foreach ($node as $key => $val) {
                if (is_string($key) && preg_match('/^(image_url|image1|image2|images|gallery|photos|image|main_image|thumb_url|desktop_image_url|image_path)$/i', (string) $key)) {
                    $this->collectTorobImageUrl($val, $out);
                }

                if (is_array($val)) {
                    $this->collectTorobImagesRecursive($val, $out, $depth + 1);
                }
            }
        }
    }

    /**
     * فاز ۲۴ — ویژگی‌ها (مشخصات فنی) از پیلود جدید و قدیمی ترب
     *
     * پیلود جدید:
     *  key_specs: [{header, items: [{key|title, value}]}]
     *  structural_specs: {headers: [{header, specs: {کلید: مقدار}}]}
     * مقدار ممکن است آرایه باشد (با «،» می‌پیوندیم) یا نشانه‌ی بخش «title» (حذف).
     */
    protected function torobSpecGroups(array $product, $title): array
    {
        $groups = [];

        $specValue = function ($value): ?string {
            if (is_array($value)) {
                $parts = [];

                foreach ($value as $item) {
                    if (is_string($item) || is_numeric($item)) {
                        $text = trim((string) $item);

                        if ($text !== '' && $text !== 'title') {
                            $parts[] = $text;
                        }
                    }
                }

                return $parts ? implode('، ', $parts) : null;
            }

            if (is_string($value) || is_numeric($value)) {
                $text = trim((string) $value);

                return ($text !== '' && $text !== 'title') ? $text : null;
            }

            return null;
        };

        // key_specs — جدول «ویژگی‌های کلیدی»
        if (! empty($product['key_specs']) && is_array($product['key_specs'])) {
            foreach ($product['key_specs'] as $block) {
                if (! is_array($block)) {
                    continue;
                }

                $header = trim((string) ($block['header'] ?? '')) ?: 'مشخصات';
                $rows = [];

                foreach ((array) ($block['items'] ?? []) as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $name = trim((string) ($item['key'] ?? $item['title'] ?? ''));
                    $value = $specValue($item['value'] ?? null);

                    if ($name !== '' && $value) {
                        $rows[] = ['name' => $name, 'value' => $value, 'special' => false];
                    }
                }

                if ($rows) {
                    $groups[] = ['name' => $header, 'specifications' => $rows];
                }
            }
        }

        // structural_specs — جدول‌های ساختاری صفحه محصول
        $structural = $product['structural_specs'] ?? null;

        if (is_array($structural) && ! empty($structural['headers']) && is_array($structural['headers'])) {
            foreach ($structural['headers'] as $block) {
                if (! is_array($block)) {
                    continue;
                }

                $header = trim((string) ($block['header'] ?? '')) ?: 'مشخصات';
                $rows = [];

                foreach ((array) ($block['specs'] ?? []) as $name => $value) {
                    $valueText = $specValue($value);
                    $name = trim((string) $name);

                    if ($name !== '' && $valueText) {
                        $rows[] = ['name' => $name, 'value' => $valueText, 'special' => false];
                    }
                }

                if ($rows) {
                    $groups[] = ['name' => $header, 'specifications' => $rows];
                }
            }
        }

        // شکل‌های قدیمی (extra_info / specifications / specs)
        if (! $groups) {
            $rawSpecs = $product['extra_info'] ?? $product['specifications'] ?? $product['specs'] ?? [];

            if (is_array($rawSpecs)) {
                $rows = [];

                foreach ($rawSpecs as $spec) {
                    if (is_array($spec)) {
                        $name = $spec['key'] ?? $spec['title'] ?? $spec['name'] ?? null;
                        $value = $specValue($spec['value'] ?? $spec['body'] ?? null);
                    } elseif (is_string($spec) && str_contains($spec, ':')) {
                        [$name, $value] = array_map('trim', explode(':', $spec, 2));
                        $value = $value !== '' ? $value : null;
                    } else {
                        continue;
                    }

                    if ($name && $value) {
                        $rows[] = ['name' => trim((string) $name), 'value' => trim((string) $value), 'special' => false];
                    }
                }

                if ($rows) {
                    $groups[] = [
                        'name' => 'مشخصات ' . ($title ?: 'محصول'),
                        'specifications' => $rows,
                    ];
                }
            }
        }

        return $groups;
    }

    /**
     * فاز ۲۴ — «تنوع قیمت بر اساس اینچ/سایز/رنگ» از آرایه variants[] ترب
     *
     * هر تب تنوع = {title, items: [محصولات همان خانواده با قیمت خودشان]}.
     * پرمخاطب‌ترین تب (بیشترین آیتم قیمت‌دار) به ردیف‌های تنوع فرم تبدیل می‌شود:
     *  - پیشوند/پسوند مشترک همه‌ی آیتم‌ها حذف می‌شود تا مقدار ویژگی بماند
     *    («تلویزیون ... سایز 43 اینچ ...» → 43)
     *  - گروه ویژگی به نام خودِ تب ساخته می‌شود (type=checkbox) و ویژگی‌ها داخل آن
     *  - قیمت هر آیتم = قیمت ردیف (ارقام فارسی price_text هم پارس می‌شود)
     *  - مقدار تکراری → ارزان‌ترین قیمت برنده است (نه ردیف تکراری)
     */
    protected function torobVariantRows(array $product): array
    {
        $variantGroups = $product['variants'] ?? [];

        if (! is_array($variantGroups)) {
            return [];
        }

        $best = null;

        foreach ($variantGroups as $group) {
            if (! is_array($group) || empty($group['title']) || ! is_array($group['items'] ?? null)) {
                continue;
            }

            $items = [];

            foreach ($group['items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $itemPrice = $this->torobToman($item['price'] ?? null);

                if (! $itemPrice) {
                    $itemPrice = $this->torobToman($item['price_text'] ?? $item['price_string'] ?? null);
                }

                $name = trim((string) ($item['name1'] ?? $item['title1'] ?? $item['name'] ?? ''));

                if ($itemPrice > 0 && $name !== '') {
                    $items[] = ['name' => $name, 'price' => $itemPrice];
                }
            }

            if (count($items) < 2) {
                continue;
            }

            if ($best === null || count($items) > count($best['items'])) {
                $best = ['title' => trim((string) $group['title']), 'items' => $items];
            }
        }

        if (! $best) {
            return [];
        }

        $best['items'] = array_slice($best['items'], 0, 30);

        // پیشوند و پسوند مشترک همه‌ی آیتم‌ها → بخش متمایز هر آیتم = مقدار ویژگی
        [$prefixLen, $suffixLen] = $this->torobSharedEdges(array_column($best['items'], 'name'));

        $attributeGroup = AttributeGroup::where('name', $best['title'])->first();

        if (! $attributeGroup) {
            $attributeGroup = AttributeGroup::create([
                'name' => $best['title'],
                'type' => 'checkbox',
            ]);
        }

        $rows = [];
        $seen = [];

        foreach ($best['items'] as $item) {
            $value = mb_substr($item['name'], $prefixLen, mb_strlen($item['name']) - $prefixLen - $suffixLen);

            // برش کاراکتری (UTF-8) جداکننده‌های ابتدا/انتها — trim بایتی فارسی را خراب می‌کند
            $value = preg_replace('#^[\s\-–—|،,/]+|[\s\-–—|،,/]+$#u', '', $value) ?? '';

            if ($value === '' || mb_strlen($value) > 60) {
                $value = mb_substr($item['name'], 0, 60);
            }

            // مقدار تکراری (مثلا دو فروشنده برای یک سایز) → ارزان‌ترین قیمت
            if (isset($seen[$value])) {
                $idx = $seen[$value];

                if ($item['price'] < $rows[$idx]['price']) {
                    $rows[$idx]['price'] = (int) $item['price'];
                }

                continue;
            }

            $attribute = Attribute::where([
                'attribute_group_id' => $attributeGroup->id,
                'name' => $value,
            ])->first();

            if (! $attribute) {
                $attribute = Attribute::create([
                    'attribute_group_id' => $attributeGroup->id,
                    'name' => $value,
                ]);
            }

            $seen[$value] = count($rows);
            $rows[] = [
                'attributes' => [$attribute->id],
                'price' => (int) $item['price'],
            ];
        }

        return $rows;
    }

    /**
     * طول پیشوند و پسوند مشترک همه‌ی رشته‌ها (برای استخراج بخش متمایز نام تنوع‌ها)
     */
    protected function torobSharedEdges(array $names): array
    {
        $names = array_values(array_filter($names, fn ($n) => is_string($n) && $n !== ''));

        if (count($names) < 2) {
            return [0, 0];
        }

        $first = $names[0];
        $prefix = '';

        for ($i = 0; $i < mb_strlen($first); $i++) {
            $char = mb_substr($first, $i, 1);

            foreach ($names as $name) {
                if (mb_substr($name, $i, 1) !== $char) {
                    break 2;
                }
            }

            $prefix .= $char;
        }

        $minLen = min(array_map('mb_strlen', $names));
        $suffix = '';

        for ($i = 1; $i <= $minLen - mb_strlen($prefix); $i++) {
            $char = mb_substr($first, mb_strlen($first) - $i, 1);

            foreach ($names as $name) {
                if (mb_substr($name, mb_strlen($name) - $i, 1) !== $char) {
                    break 2;
                }
            }

            $suffix = $char . $suffix;
        }

        return [mb_strlen($prefix), mb_strlen($suffix)];
    }

    /**
     * فاز ۲۴ — تبدیل قیمت ترب به عدد تومان
     * ارقام فارسی/عربی و جداکننده‌های «٬ ٫ , فاصله» و واژه‌ی «تومان» پشتیبانی می‌شود
     */
    protected function torobToman($value): int
    {
        if (is_int($value) || is_float($value)) {
            return (int) round((float) $value);
        }

        if (! is_string($value) || trim($value) === '') {
            return 0;
        }

        $map = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];

        $text = strtr(trim($value), $map);

        if (! preg_match('/\d[\d٬,\s٫\.]*/u', $text, $m)) {
            return 0;
        }

        $digits = preg_replace('/[^\d]/u', '', $m[0]);

        return $digits !== '' ? (int) $digits : 0;
    }

    /**
     * جمع‌آوری URL تصویر ترب (مطلق / //protocol-relative / آرایه)
     */
    protected function collectTorobImageUrl($val, array &$out): void
    {
        if (is_array($val)) {
            foreach ($val as $v) {
                $this->collectTorobImageUrl($v, $out);
            }

            return;
        }

        if (! is_string($val) || $val === '') {
            return;
        }

        $val = trim($val);

        if (str_starts_with($val, 'http://') || str_starts_with($val, 'https://')) {
            $out[] = $val;
        } elseif (str_starts_with($val, '//')) {
            $out[] = 'https:' . $val;
        } elseif (str_starts_with($val, '/') && ! str_contains($val, ' ')) {
            $out[] = 'https://gt.torob.com' . $val;
        }
    }

    /** استخراج کد prk از آدرس یا ورودی مستقیم (فاز ۲۴: prk می‌تواند UUID با خط تیره باشد) */
    protected function extractTorobKey(string $input): ?string
    {
        $input = trim($input);

        if (preg_match('~torob\.com/(?:m/)?p/([A-Za-z0-9-]+)~iu', $input, $m)) {
            return trim($m[1], '-');
        }

        // آدرس کوتاهی مثل torob.com/p/xxx بدون دامنه یا فقط کد
        if (preg_match('~^/?p/([A-Za-z0-9-]+)~iu', $input, $m)) {
            return trim($m[1], '-');
        }

        if (preg_match('/^[A-Za-z0-9-]{6,60}$/', $input)) {
            return trim($input, '-');
        }

        return null;
    }

    /**
     * دریافت HTML صفحه ترب با هدر مرورگر — [html|null, error|null]
     */
    protected function fetchTorobPage(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: fa-IR,fa;q=0.9,en;q=0.8',
            'Referer: https://torob.com/',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Upgrade-Insecure-Requests: 1',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        ]);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_errno($ch) ? curl_error($ch) : null;
        curl_close($ch);

        if ($curlError) {
            return [null, 'Curl error: ' . $curlError];
        }

        if ($httpCode !== 200 || ! is_string($html)) {
            return [null, "HTTP code: {$httpCode}"];
        }

        if (str_contains($html, 'arcaptcha') && ! str_contains($html, '__NEXT_DATA__') && ! str_contains($html, 'application/ld+json')) {
            return [null, 'HTTP code: 403'];
        }

        return [$html, null];
    }

    /**
     * فاز ۲۳ — پارس __NEXT_DATA__ صفحات Next.js ترب
     *
     * ترب با Next.js است و کل داده‌ی صفحه در <script id="__NEXT_DATA__"> است.
     * ساختار تو در تو بین نسخه‌ها فرق می‌کند؛ پس بازگشتی به دنبال اولین گره‌ای
     * می‌گردیم که «شکل» محصول ترب را دارد (title1/min_price/results...).
     */
    protected function parseTorobNextData(string $html): ?array
    {
        if (! preg_match('#<script[^>]*id=["\']__NEXT_DATA__["\'][^>]*>(.*?)</script>#is', $html, $m)) {
            return null;
        }

        $data = json_decode((string) $m[1], true);

        if (! is_array($data)) {
            return null;
        }

        return $this->findTorobProductNode($data, 0);
    }

    /**
     * جستجوی بازگشتی (با عمق محدود) گره‌ی محصول در JSON ترب
     */
    protected function findTorobProductNode(array $node, int $depth): ?array
    {
        if ($depth > 12) {
            return null;
        }

        if ($this->isTorobProductPayload($node)) {
            return $node;
        }

        foreach ($node as $child) {
            if (is_array($child)) {
                $found = $this->findTorobProductNode($child, $depth + 1);

                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * پارس JSON-LD (schema.org) از HTML ترب — [title, image[], price, description, category]
     */
    protected function parseTorobJsonLd(string $html): ?array
    {
        $out = null;

        if (! preg_match_all('#<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $matches)) {
            return null;
        }

        foreach ($matches[1] as $raw) {
            $raw = preg_replace('/^<!--/', '', trim($raw));
            $raw = preg_replace('/-->$/', '', trim((string) $raw));

            $data = json_decode((string) $raw, true);

            if (! is_array($data)) {
                continue;
            }

            foreach ([$data, ...(array) ($data['@graph'] ?? [])] as $node) {
                if (! is_array($node)) {
                    continue;
                }

                $type = $node['@type'] ?? '';

                if ((is_string($type) && strtolower($type) === 'product') || (is_array($type) && in_array('Product', array_map('strtolower', $type)))) {
                    $offers = $node['offers'] ?? [];

                    if (isset($offers[0]) && is_array($offers)) {
                        $offers = $offers[0];
                    }

                    $out = [
                        'title' => $node['name'] ?? null,
                        'image' => isset($node['image']) ? (array) $node['image'] : [],
                        'price' => $offers['price'] ?? $offers['lowPrice'] ?? null,
                        'description' => $node['description'] ?? null,
                        'category' => $node['category'] ?? null,
                    ];

                    break 2;
                }
            }
        }

        return $out;
    }


    public function resizeAndSaveSiteImage($imageUrl, $savePath, $width = null, $height = null)
    {
        $ch = curl_init($imageUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // امنیتی: گواهی TLS تأیید شود
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // امنیتی: تطابق هاست گواهی
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // امنیتی: گواهی TLS تأیید شود
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // امنیتی: تطابق هاست گواهی
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // فاز ۲۳: هدرهای شبیه مرورگر — ترب بدون Referer/UA کامل 403/490 می‌دهد
        $headers = [
            'Accept: application/json, text/plain, */*',
            'Accept-Charset: utf-8',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        ];

        if (str_contains($url, 'torob.com')) {
            $headers[] = 'Referer: https://torob.com/';
            $headers[] = 'Origin: https://torob.com';
            $headers[] = 'X-Requested-With: XMLHttpRequest';
            $headers[] = 'Accept-Language: fa-IR,fa;q=0.9,en;q=0.8';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

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
