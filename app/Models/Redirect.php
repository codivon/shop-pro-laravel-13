<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Redirect extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        // ★ فاز ۲۲: باطل‌سازی کش میدلور ریدایرکت پس از هر تغییر
        foreach (['saved', 'deleted'] as $event) {
            static::$event(function () {
                Cache::forget('redirects.map');
                Cache::forget('redirects.table_exists');
            });
        }
    }
}
