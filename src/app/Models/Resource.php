<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    protected $fillable = [
        'name', 'description', 'type',
        'capacity', 'floor', 'price_per_hour', 'is_active'
    ];
}
