<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * فقط حروف فارسی و فاصله (پورت رول قدیمی persian_alpha که هرگز ثبت نشده بود)
 */
class PersianAlpha implements Rule
{
    public function passes($attribute, $value)
    {
        if ($value === null || $value === '') {
            return true;
        }

        return is_string($value) && preg_match('/^[\x{0600}-\x{06FF}\x{200c}\s]+$/u', $value) === 1;
    }

    public function message()
    {
        return 'این فیلد فقط می‌تواند شامل حروف فارسی باشد.';
    }
}
