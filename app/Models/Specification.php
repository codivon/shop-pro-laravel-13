<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;

class Specification extends Model
{
    use Filterable;

    protected $guarded = ['id'];

    /**
     * گروه‌های مرتبط از طریق پیوت سه‌طرفهٔ spec_type_specification.
     * قبلاً belongsToMany بدون نام جدول بود و لاراول دنبال جدولِ
     * ناموجود specification_specification_group می‌رفت (خطای 1146).
     */
    public function group()
    {
        return $this->belongsToMany(SpecificationGroup::class, 'spec_type_specification', 'specification_id', 'specification_group_id')
            ->withPivot(['spec_type_id', 'group_ordering', 'specification_ordering'])
            ->withTimestamps()
            ->orderBy('group_ordering')
            ->distinct();
    }
}
