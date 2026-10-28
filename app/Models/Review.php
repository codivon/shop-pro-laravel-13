<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $guarded = ['id'];

    public function commentable()
    {
        return $this->morphTo();
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // فاز ۱۱۸ — فروشنده و تنوعی که خریدار واقعاً از آن‌ها خرید کرده (برای امتیاز فروشنده)
    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function price()
    {
        return $this->belongsTo(Price::class);
    }
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function points()
    {
        return $this->hasMany(ReviewPoint::class);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function refreshLikesCount()
    {
        $this->update([
            'likes_count'    => $this->likes()->where('type', 'like')->count(),
            'dislikes_count' => $this->likes()->where('type', 'dislike')->count(),
        ]);
    }

    public function scopeFilter($query)
    {
        $request = request();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        switch ($request->ordering) {
            case 'oldest': {
                    $query->oldest();
                    break;
                }
            default: {
                    $query->latest();
                }
        }

        return $query;
    }
}
