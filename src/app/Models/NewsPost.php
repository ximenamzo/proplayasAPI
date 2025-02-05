<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'creator_type',
        'creator_id',
        'post_date',
        'category',
        'tags',
        'image',
        'link'
    ];
}
