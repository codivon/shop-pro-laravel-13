<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

class Role extends Model
{
    use sluggable;

    protected $guarded = ['id'];

    public function sluggable() : array
    {
        return [
            'slug' => [
                'source' => 'title',
            ],
        ];
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /** مدیران دارای این نقش (پیوت admin_role — برای withCount در پنل جدید) */
    public function admins()
    {
        return $this->belongsToMany(Admin::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
}
