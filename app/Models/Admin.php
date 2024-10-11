<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;


class Admin extends Model
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'image',
        'job_tittle',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($image) => $image ? url('/images-admin/'.$image) : null,
        );
    }
}
