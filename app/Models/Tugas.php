<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tugas extends Model
{
    use HasFactory;

    protected $table = 'tugas';

    protected $fillable = [
        'user_id',
        'jurnal_id',
        'deadline',
        'title',
        'description',
        'image',
        'status',
    ];

    protected $casts = [
        'jurnal_id' => 'integer',
        'user_id' => 'integer',
        'deadline' => 'datetime:Y-m-d ',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jurnal()
    {
        return $this->belongsTo(Jurnal::class);
    }
}
