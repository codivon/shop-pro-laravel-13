<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * حروف فارسی/انگلیسی + اعداد + فاصله (پورت رول قدیمی persian_alpha_eng_num که هرگز ثبت نشده بود)
 */
class PersianAlphaEngNum implements Rule
{
    public function passes($attribute, $value)
    {
        if ($value === null || $value === '') {
            return true;
        }

        return is_string($value) && preg_match('/^[\x{0600}-\x{06FF}\x{200c}a-zA-Z0-9\s]+$/u', $value) === 1;
    }

    public function message()
    {
        return 'این فیلد فقط می‌تواند شامل حروف فارسی و انگلیسی و اعداد باشد.';
    }
}
