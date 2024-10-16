<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Jurnal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name_title',
        'activity',
        'image',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tugas()
    {
        return $this->hasMany(Tugas::class, 'jurnal_id');
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($image) => $image ? url('/jurnal/'.$image) : null,
        );
    }

}
