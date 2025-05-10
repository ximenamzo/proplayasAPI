<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $table = 'projects';

    protected $fillable = [
        'title',
        'description',
        'author_id',
        'date',
        'location',
        'link',
        'cover_image',
        'file_path',
        'participants',
        'status',
    ];

    protected $casts = [
        'date' => 'datetime',
        'participants' => 'array', // JSON
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
