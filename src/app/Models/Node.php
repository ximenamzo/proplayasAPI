<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Node extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'type',
        'code',
        'name',
        'country',
        'city',
        'joined_in',
        'members_count',
        'leader_name',
        'email',
        'password',
        'website',
        'activity_level',
        'memorandum'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function members()
    {
        return $this->hasMany(Member::class);
    }
}
