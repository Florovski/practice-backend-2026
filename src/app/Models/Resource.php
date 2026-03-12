<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    protected $fillable = [
        'name', 'description', 'type',
        'capacity', 'floor', 'price_per_hour', 'is_active'
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    public function getAverageRatingAttribute()
    {
        return round($this->reviews()->avg('rating'), 1);
    }
}
