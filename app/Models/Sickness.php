<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sickness extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'symptoms',
        'image',
        'allowed',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
