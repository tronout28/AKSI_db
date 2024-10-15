<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Homeward extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'latitude', 
        'longitude', 
        'check_out_time', 
        ]; 
    
        public function user()
        {
            return $this->belongsTo(User::class);
        }  
}
