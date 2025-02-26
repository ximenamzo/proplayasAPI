<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollaborativeProject extends Model
{
    use HasFactory;

    protected $table = 'collaborative_projects';

    protected $fillable = [
        'title',
        'description',
        'image',
        'date',
        'location',
        'link',
        'participants',
    ];

    protected $casts = [
        'participants' => 'array', // JSON
    ];
}
