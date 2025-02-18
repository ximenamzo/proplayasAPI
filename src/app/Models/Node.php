<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Node extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'leader_id',
        'code',
        'type',
        'name',
        'profile_picture',
        'country',
        'city',
        'coordinates',
        'alt_places',
        'joined_in',
        'members_count',
        'id_photo',
        'node_email',
        'website',
        'facebook',
        'instagram',
        'youtube',
        'memorandum',
        'status',
    ];

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members()
    {
        return $this->hasMany(Member::class);
    }
}
