<?php

namespace App\Models;

use App\Traits\Languageable;
use Illuminate\Database\Eloquent\Model;

class Widget extends Model
{
    use Languageable;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function options()
    {
        return $this->hasMany(WidgetOption::class);
    }

    public function option($key, $default = null)
    {
        return $this->options->where('key', $key)->first()->value ?? $default;
    }

    public function type()
    {
        return config('front.'.$this->page.'-widgets.' . $this->key . '.title', $this->key);
    }
}
