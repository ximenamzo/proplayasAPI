<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomePageContent extends Model
{
    use HasFactory;

    protected $table = 'homepage_content'; 

    protected $fillable = [
        'section_name',
        'content',
    ];
}
