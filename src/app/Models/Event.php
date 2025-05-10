<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'description',
        'author_id',
        'date',
        'link',
        'format',
        'location',
        'file_path',
        'cover_image',
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
