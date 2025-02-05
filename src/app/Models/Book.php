<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'publication_date',
        'isbn',
        'description',
        'link',
        'file_path',
        'cover_image',
        'creator_type',
        'creator_id'
    ];
}
