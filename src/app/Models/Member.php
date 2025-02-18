<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'user_id',
        'node_id',
        'member_code',
        'role',
        'degree',
        'postgraduate',
        'expertise_area',
        'research_work',
        'profile_picture',
        'whatsapp',
        'skype',
        'website',
        'facebook',
        'instagram',
        'youtube',
        'linkedin',
        'researchgate',
        'status',
    ];

    /**
     * Relación: Un miembro pertenece a un usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación: Un miembro pertenece a un nodo.
     */
    public function node()
    {
        return $this->belongsTo(Node::class, 'node_id');
    }
}
