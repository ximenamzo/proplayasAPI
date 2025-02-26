<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    use HasFactory;

    protected $fillable = [
        'leader_id',
        'code',
        'type',
        'name',
        'profile_picture',
        'about',
        'country',
        'city',
        'ip_address',
        'coordinates',
        'alt_places',
        'joined_in',
        'members_count',
        'id_photo',
        'social_media',
        'status',
    ];

    protected $casts = [
        'social_media' => 'array', // JSON
    ];

    /**
     * Relación: Un nodo tiene UN líder (usuario con rol node_leader).
     */
    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    /**
     * Relación: Un nodo tiene muchos miembros.
     */
    public function members()
    {
        return $this->hasMany(Member::class, 'node_id');
    }
}
