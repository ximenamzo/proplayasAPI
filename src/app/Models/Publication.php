<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'description',
        'link',
        'doi',
        'issn',
        'file_path',
        'cover_image',
        'creator_type',
        'creator_id'
    ];
}
