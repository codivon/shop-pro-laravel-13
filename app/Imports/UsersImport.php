<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class UsersImport implements ToModel, WithStartRow
{
    public $request;
    protected $errors = [];
    protected $successCount = 0;
    protected $failCount = 0;
    protected $duplicates = [];
    protected $rowNumber = 1;
    protected $processedHashes = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->updateDuplicate = $request->has('update_duplicate') && $request->update_duplicate == 1;
    }

    public function model(array $file)
    {
        // افزایش شماره ردیف
        $this->rowNumber++;

        // 1️⃣ رد کردن سطرهای کاملاً خالی (همه سلول‌ها خالی)
        if (empty(array_filter($file))) {
            return null;
        }

        // 2️⃣ جلوگیری از پردازش تکراری (در صورت بروز)
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

        // 3️⃣ اگر بعد از فیلتر کردن، هیچ داده‌ای باقی نمانده باشد
        if (empty(array_filter($combinedArray))) {
            return null;
        }

        // 4️⃣ نادیده گرفتن سطرهایی که first_name یا mobile ندارند
        if (empty($combinedArray['first_name']) || empty($combinedArray['mobile'])) {
            return null;
        }

        // 5️⃣ نرمال‌سازی داده‌ها
        $combinedArray = $this->normalizeData($combinedArray);

        // تنظیم username = mobile
        if (!empty($combinedArray['mobile'])) {
            $combinedArray['username'] = $combinedArray['mobile'];
        }

        // 6️⃣ اعتبارسنجی
        $validationResult = $this->validateUserData($combinedArray);
        if ($validationResult !== true) {
            $this->failCount++;
            $this->errors[] = [
                'row' => $this->rowNumber,
                'name' => ($combinedArray['first_name'] ?? '') . ' ' . ($combinedArray['last_name'] ?? ''),
                'error' => $validationResult,
                'data' => $combinedArray
            ];
            return null;
        }

        // 7️⃣ جستجوی کاربر
        $user = null;
        if (!empty($combinedArray['mobile'])) {
            $user = User::where('mobile', $combinedArray['mobile'])->first();
        }
        if (!$user && !empty($combinedArray['email'])) {
            $user = User::where('email', $combinedArray['email'])->first();
        }

        if ($user) {
            if ($this->updateDuplicate) {
                $updateResult = $this->updateExistingUser($user, $combinedArray);
                if ($updateResult !== true) {
                    $this->failCount++;
                    $this->errors[] = [
                        'row' => $this->rowNumber,
                        'name' => ($combinedArray['first_name'] ?? '') . ' ' . ($combinedArray['last_name'] ?? ''),
                        'error' => $updateResult,
                        'data' => $combinedArray
                    ];
                    return null;
                }
                $this->successCount++;
                return null;
            } else {
                // در غیر این صورت، به عنوان تکراری ثبت کن (ناموفق)
                $this->failCount++;
                $this->duplicates[] = [
                    'row' => $this->rowNumber,
                    'name' => ($combinedArray['first_name'] ?? '') . ' ' . ($combinedArray['last_name'] ?? ''),
                    'error' => 'کاربر با این موبایل یا ایمیل قبلاً ثبت شده است.',
                    'data' => $combinedArray
                ];
                return null;
            }
        }

        // 8️⃣ بررسی تکراری بودن (قبل از ایجاد)
        $duplicateCheck = $this->checkDuplicateFields($combinedArray);
        if ($duplicateCheck !== true) {
            $this->failCount++;
            $this->duplicates[] = [
                'row' => $this->rowNumber,
                'name' => ($combinedArray['first_name'] ?? '') . ' ' . ($combinedArray['last_name'] ?? ''),
                'error' => $duplicateCheck,
                'data' => $combinedArray
            ];
            return null;
        }

        // 9️⃣ ایجاد کاربر جدید
        $createResult = $this->createNewUser($combinedArray);
        if ($createResult !== true) {
            $this->failCount++;
            $this->errors[] = [
                'row' => $this->rowNumber,
                'name' => ($combinedArray['first_name'] ?? '') . ' ' . ($combinedArray['last_name'] ?? ''),
                'error' => $createResult,
                'data' => $combinedArray
            ];
            return null;
        }

        $this->successCount++;
        return null;
    }

    // ---------- متدهای کمکی (همانند قبل) ----------
    private function normalizeData($data)
    {
        // نرمال‌سازی موبایل
        if (!empty($data['mobile'])) {
            $mobile = preg_replace('/[^0-9]/', '', (string)$data['mobile']);
            if (strlen($mobile) == 10 && substr($mobile, 0, 1) != '0') {
                $mobile = '0' . $mobile;
            }
            if (strlen($mobile) == 11 && substr($mobile, 0, 2) == '09') {
                $data['mobile'] = $mobile;
            } else {
                $data['mobile'] = null;
            }
        }

        // نرمال‌سازی ایمیل
        if (!empty($data['email'])) {
            $email = trim($data['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $data['email'] = null;
            }
        }

        // نرمال‌سازی رمز عبور
        if (!empty($data['password'])) {
            $password = trim((string)$data['password']);
            if (is_numeric($password) && strlen($password) < 6) {
                $data['password'] = null;
            }
        }

        return $data;
    }

    private function validateUserData($data)
    {
        $missing = [];
        if (empty($data['first_name'])) $missing[] = 'نام';
        if (empty($data['mobile'])) $missing[] = 'موبایل';
        if (empty($data['email'])) $missing[] = 'ایمیل';
        if (empty($data['password'])) $missing[] = 'رمز عبور';

        if (!empty($missing)) {
            return 'فیلدهای ' . implode('، ', $missing) . ' الزامی هستند.';
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return "فرمت ایمیل '{$data['email']}' نامعتبر است.";
        }

        if (!preg_match('/^09[0-9]{9}$/', $data['mobile'])) {
            return "فرمت موبایل '{$data['mobile']}' نامعتبر است.";
        }

        if (strlen($data['password']) < 6) {
            return 'رمز عبور باید حداقل ۶ کاراکتر باشد.';
        }

        return true;
    }

    private function checkDuplicateFields($data)
    {
        $errors = [];
        if (!empty($data['mobile']) && User::where('mobile', $data['mobile'])->exists()) {
            $errors[] = "موبایل '{$data['mobile']}' قبلاً ثبت شده است.";
        }
        if (!empty($data['email']) && User::where('email', $data['email'])->exists()) {
            $errors[] = "ایمیل '{$data['email']}' قبلاً ثبت شده است.";
        }
        return empty($errors) ? true : implode(' - ', $errors);
    }

    private function createNewUser($data)
    {
        try {
            $user = new User();
            $fields = ['first_name', 'last_name', 'email', 'mobile', 'national_code', 'birth_date', 'card_number'];
            foreach ($fields as $field) {
                if (!empty($data[$field])) {
                    $user->$field = $data[$field];
                }
            }

            if (!empty($data['mobile'])) {
                $user->username = $data['mobile'];
            } elseif (!empty($data['email'])) {
                $user->username = explode('@', $data['email'])[0];
            }

            $user->password = Hash::make($data['password']);
            $user->save();

            if (!empty($data['image'])) {
                $this->handleUserImage($user, $data['image']);
            }

            return true;
        } catch (\Exception $e) {
            return 'خطا در ایجاد کاربر: ' . $e->getMessage();
        }
    }

    private function updateExistingUser($user, $data)
    {
        try {
            $updateData = [];
            $fields = ['first_name', 'last_name', 'email', 'mobile', 'national_code', 'birth_date', 'card_number'];
            foreach ($fields as $field) {
                if (!empty($data[$field])) {
                    if (in_array($field, ['email', 'mobile'])) {
                        $exists = User::where($field, $data[$field])->where('id', '!=', $user->id)->exists();
                        if ($exists) continue;
                    }
                    $updateData[$field] = $data[$field];
                }
            }

            if (!empty($data['mobile'])) {
                $exists = User::where('username', $data['mobile'])->where('id', '!=', $user->id)->exists();
                if (!$exists) {
                    $updateData['username'] = $data['mobile'];
                }
            }

            if (!empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            if (!empty($data['image'])) {
                $this->handleUserImage($user, $data['image']);
            }

            return true;
        } catch (\Exception $e) {
            return 'خطا در به‌روزرسانی کاربر: ' . $e->getMessage();
        }
    }

    private function handleUserImage($user, $imageUrl)
    {

        try {
            // ایجاد پوشه
            $path = public_path('/uploads/users/avatars');
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }

            // حذف تصویر قبلی
            if ($user->image && file_exists(public_path($user->image))) {
                @unlink(public_path($user->image));
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
                $name = 'img-' . time() . '-' . $user->id . '.' . $ext;
                $fullPath = $path . '/' . $name;

                if (file_put_contents($fullPath, $content) !== false) {
                    $user->image = '/uploads/users/avatars/' . $name;
                    $user->save();
                    \Log::info('تصویر با موفقیت ذخیره شد: ' . $imageUrl);
                } else {
                    \Log::warning('ذخیره فایل ناموفق: ' . $fullPath);
                }
            } else {
                \Log::warning('دانلود تصویر ناموفق: ' . $imageUrl . ' (کد: ' . $httpCode . ')');
            }
        } catch (\Exception $e) {
            \Log::error('خطا در ذخیره تصویر کاربر: ' . $e->getMessage());
        }


    }

    public function getReport()
    {
        $allFails = array_merge($this->errors, $this->duplicates);
        return [
            'success_count' => $this->successCount,
            'fail_count' => $this->failCount,  // شامل تکراری‌ها نیز هست
            'total_count' => $this->successCount + $this->failCount,
            'failed_rows' => $allFails,        // شامل هر دو نوع خطا
            'errors' => $this->errors,
            'duplicates' => $this->duplicates,
        ];
    }


    public function uniqueBy()
    {
        return 'mobile';
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
