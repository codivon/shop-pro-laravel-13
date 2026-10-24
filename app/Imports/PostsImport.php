<?php

namespace App\Imports;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Morilog\Jalali\Jalalian;

class PostsImport implements ToModel, WithStartRow
{
    public $request;
    protected $errors = [];
    protected $successCount = 0;
    protected $failCount = 0;
    protected $duplicates = [];
    protected $rowNumber = 1;
    protected $processedHashes = [];
    protected $updateDuplicate;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->updateDuplicate = $request->has('update_duplicate') && $request->update_duplicate == 1;
    }

    public function model(array $file)
    {
        $this->rowNumber++;

        // رد کردن سطرهای کاملاً خالی
        if (empty(array_filter($file))) {
            return null;
        }

        // جلوگیری از پردازش تکراری
        $rowHash = md5(serialize($file));
        if (in_array($rowHash, $this->processedHashes)) {
            return null;
        }
        $this->processedHashes[] = $rowHash;

        // ترکیب داده‌ها بر اساس فیلترهای انتخاب‌شده
        $row = 0;
        $Alldata = [];
        $allArrays = [];
        $fileCount = count($file) - 1;
        $filtersCount = count($this->request->filters) - 1;

        if ($filtersCount > $fileCount) {
            return null;
        }

        foreach ($this->request->filters as $key => $filter) {
            $Alldata[] = [$key => $file[$row]];
            $row++;
        }

        $count = count($Alldata) - 1;
        for ($i = 0; $i <= $count; $i++) {
            $allArrays[] = $Alldata[$i];
        }
        $combinedArray = call_user_func_array('array_merge', $allArrays);

        // نادیده گرفتن سطرهای فاقد title
        if (empty($combinedArray['title'])) {
            return null;
        }

        // نرمال‌سازی داده‌ها
        $combinedArray = $this->normalizeData($combinedArray);

        // تنظیم slug
        if (empty($combinedArray['slug'])) {
            $combinedArray['slug'] = sluggable_helper_function($combinedArray['title']);
        }

        // اعتبارسنجی
        $validationResult = $this->validatePostData($combinedArray);
        if ($validationResult !== true) {
            $this->failCount++;
            $this->errors[] = [
                'row' => $this->rowNumber,
                'title' => $combinedArray['title'] ?? '',
                'error' => $validationResult,
                'data' => $combinedArray
            ];
            return null;
        }

        // جستجوی نوشته
        $post = Post::where('slug', $combinedArray['slug'])->first();

        if ($post) {
            // اگر نوشته موجود است
            if ($this->updateDuplicate) {
                // بروزرسانی
                $updateResult = $this->updateExistingPost($post, $combinedArray);
                if ($updateResult !== true) {
                    $this->failCount++;
                    $this->errors[] = [
                        'row' => $this->rowNumber,
                        'title' => $combinedArray['title'] ?? '',
                        'error' => $updateResult,
                        'data' => $combinedArray
                    ];
                    return null;
                }
                $this->successCount++;
                return null;
            } else {
                // تکراری
                $this->failCount++;
                $this->duplicates[] = [
                    'row' => $this->rowNumber,
                    'title' => $combinedArray['title'] ?? '',
                    'error' => 'نوشته با این Slug قبلاً ثبت شده است.',
                    'data' => $combinedArray
                ];
                return null;
            }
        }

        // ایجاد نوشته جدید
        $createResult = $this->createNewPost($combinedArray);
        if ($createResult !== true) {
            $this->failCount++;
            $this->errors[] = [
                'row' => $this->rowNumber,
                'title' => $combinedArray['title'] ?? '',
                'error' => $createResult,
                'data' => $combinedArray
            ];
            return null;
        }

        $this->successCount++;
        return null;
    }

    /**
     * نرمال‌سازی داده‌ها
     */
    private function normalizeData($data)
    {
        // تعداد بازدید
        if (!empty($data['view'])) {
            $data['view'] = (int) $data['view'];
        }

        // وضعیت انتشار
        if (!empty($data['published'])) {
            $data['published'] = (int) $data['published'];
        }

        if (!empty($data['publish_date'])) {
            $data['publish_date'] = $this->convertJalaliToGregorian($data['publish_date']);
        }

        return $data;
    }

    private function convertJalaliToGregorian($jalaliDate)
    {
        $jalaliDate = trim($jalaliDate);
        if (empty($jalaliDate)) {
            return null;
        }

        try {
            // جایگزین اسلش با خط تیره برای سازگاری با فرمت Jalalian
            $normalized = str_replace('/', '-', $jalaliDate);

            // بررسی وجود زمان
            if (strpos($normalized, ':') !== false) {
                // دارای زمان
                $dateTime = Jalalian::fromFormat('Y-m-d H:i:s', $normalized)->toCarbon();
            } else {
                // فقط تاریخ
                $dateTime = Jalalian::fromFormat('Y-m-d', $normalized)->toCarbon();
                // تنظیم زمان 00:00:00
                $dateTime->setTime(0, 0, 0);
            }

            return $dateTime->toDateTimeString(); // 'Y-m-d H:i:s'
        } catch (\Exception $e) {
            // اگر تبدیل شمسی ناموفق بود، امتحان به عنوان میلادی
            try {
                $carbon = \Carbon\Carbon::parse($jalaliDate);
                return $carbon->toDateTimeString();
            } catch (\Exception $e2) {
                // در نهایت null برگردان
                return null;
            }
        }
    }



    /**
     * اعتبارسنجی داده‌های نوشته
     */
    private function validatePostData($data)
    {
        if (empty($data['title'])) {
            return 'فیلد عنوان الزامی است.';
        }

        if (empty($data['slug'])) {
            return 'فیلد Slug الزامی است.';
        }

        return true;
    }

    /**
     * ایجاد نوشته جدید
     */
    private function createNewPost($data)
    {
        try {
            $post = new Post();
            $fields = ['title', 'slug', 'content', 'meta_title', 'meta_description', 'publish_date', 'view', 'published'];

            foreach ($fields as $field) {
                if (isset($data[$field]) && !empty($data[$field])) {
                    $post->$field = $data[$field];
                }
            }

            $post->admin_id=$this->request->admin_id;

            $post->save();

            // پردازش تصویر
            if (!empty($data['image'])) {
                $this->handlePostImage($post, $data['image']);
            }

            // پردازش تگ‌ها
            if (!empty($data['tags'])) {
                $this->handlePostTags($post, $data['tags']);
            }

            return true;
        } catch (\Exception $e) {
            return 'خطا در ایجاد نوشته: ' . $e->getMessage();
        }
    }

    /**
     * به‌روزرسانی نوشته موجود
     */
    private function updateExistingPost($post, $data)
    {
        try {
            $updateData = [];
            $fields = ['title', 'slug', 'content', 'meta_title', 'meta_description', 'publish_date', 'view', 'published'];

            foreach ($fields as $field) {
                if (isset($data[$field]) && !empty($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (!empty($updateData)) {
                $post->update($updateData);
            }

            // پردازش تصویر
            if (!empty($data['image'])) {
                $this->handlePostImage($post, $data['image']);
            }

            // پردازش تگ‌ها
            if (!empty($data['tags'])) {
                $this->handlePostTags($post, $data['tags']);
            }

            return true;
        } catch (\Exception $e) {
            return 'خطا در به‌روزرسانی نوشته: ' . $e->getMessage();
        }
    }

    /**
     * پردازش تصویر نوشته
     */
    private function handlePostImage($post, $imageUrl)
    {
        try {
            // ایجاد پوشه
            $path = public_path('/uploads/posts');
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }

            // حذف تصویر قبلی
            if ($post->image && file_exists(public_path($post->image))) {
                @unlink(public_path($post->image));
            }

            // دانلود با cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $imageUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // امنیتی: گواهی TLS تأیید شود
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // امنیتی: تطابق هاست گواهی
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            $content = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // بررسی موفقیت
            if ($httpCode == 200 && $content !== false && !empty($content)) {
                $ext = pathinfo($imageUrl, PATHINFO_EXTENSION) ?: 'jpg';
                $name = 'img-' . time() . '-' . $post->id . '.' . $ext;
                $fullPath = $path . '/' . $name;

                if (file_put_contents($fullPath, $content) !== false) {
                    $post->image = '/uploads/posts/' . $name;
                    $post->save();
                    \Log::info('تصویر با موفقیت ذخیره شد: ' . $imageUrl);
                } else {
                    \Log::warning('ذخیره فایل ناموفق: ' . $fullPath);
                }
            } else {
                \Log::warning('دانلود تصویر ناموفق: ' . $imageUrl . ' (کد: ' . $httpCode . ')');
            }
        } catch (\Exception $e) {
            \Log::error('خطا در ذخیره تصویر نوشته: ' . $e->getMessage());
        }
    }

    /**
     * پردازش تگ‌های نوشته
     */
    private function handlePostTags($post, $tagsString)
    {
        $allTags = explode(',', $tagsString);
        $tagIds = [];

        foreach ($allTags as $tagName) {
            $tagName = trim($tagName);
            if (empty($tagName)) continue;

            $tagSlug = sluggable_helper_function($tagName);
            $tag = Tag::where('slug', $tagSlug)->first();

            if (!$tag) {
                $tag = Tag::create([
                    'name' => $tagName,
                    'slug' => $tagSlug,
                    'lang' => 'fa',
                ]);
            }

            $tagIds[] = $tag->id;
        }

        // اتصال تگ‌ها به نوشته (بدون حذف تگ‌های قبلی)
        if (!empty($tagIds)) {
            foreach ($tagIds as $tagId) {
                $exists = DB::table('taggables')
                    ->where('tag_id', $tagId)
                    ->where('taggable_id', $post->id)
                    ->where('taggable_type', 'App\Models\Post')
                    ->exists();

                if (!$exists) {
                    DB::table('taggables')->insert([
                        'tag_id' => $tagId,
                        'taggable_id' => $post->id,
                        'taggable_type' => 'App\Models\Post',
                    ]);
                }
            }
        }
    }

    /**
     * دریافت گزارش نهایی
     */
    public function getReport()
    {
        $allFails = array_merge($this->errors, $this->duplicates);

        // حذف خطاهای تکراری
        $uniqueErrors = [];
        $seen = [];
        foreach ($this->errors as $e) {
            $key = md5(serialize($e['data']));
            if (!in_array($key, $seen)) {
                $uniqueErrors[] = $e;
                $seen[] = $key;
            }
        }

        $uniqueDuplicates = [];
        $seenDup = [];
        foreach ($this->duplicates as $d) {
            $key = md5(serialize($d['data']));
            if (!in_array($key, $seenDup)) {
                $uniqueDuplicates[] = $d;
                $seenDup[] = $key;
            }
        }

        return [
            'success_count' => $this->successCount,
            'fail_count' => count($uniqueErrors) + count($uniqueDuplicates),
            'total_count' => $this->successCount + count($uniqueErrors) + count($uniqueDuplicates),
            'failed_rows' => array_merge($uniqueErrors, $uniqueDuplicates),
            'errors' => $uniqueErrors,
            'duplicates' => $uniqueDuplicates,
            'update_duplicate' => $this->updateDuplicate,
        ];
    }
    public function uniqueBy()
    {
        return 'slug';
    }
    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function startRow(): int
    {
        return 2;
    }
}
