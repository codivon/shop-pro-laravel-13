<?php

namespace App\Models;

use App\Traits\Languageable;
use Illuminate\Database\Eloquent\Model;

class SpecificationGroup extends Model
{
    use Languageable;

    protected $guarded = ['id'];

    /**
     * مشخصه‌های مرتبط از طریق پیوت سه‌طرفهٔ spec_type_specification.
     * قبلاً belongsToMany بدون نام جدول بود و لاراول دنبال جدولِ
     * ناموجود specification_specification_group می‌رفت (خطای 1146 در «اعمال قالب»).
     */
    public function specifications()
    {
        return $this->belongsToMany(Specification::class, 'spec_type_specification', 'specification_group_id', 'specification_id')
            ->withPivot(['spec_type_id', 'group_ordering', 'specification_ordering'])
            ->withTimestamps()
            ->orderBy('specification_ordering')
            ->distinct();
    }
}
