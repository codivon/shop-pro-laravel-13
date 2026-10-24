<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fild extends Model
{
    protected $guarded = ['id'];

    public function fieldValues()
    {
        return $this->hasMany(FieldValue::class, 'field_id');
    }
}
