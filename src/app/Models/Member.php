<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Member extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'node_id',
        'member_code',
        'name',
        'email',
        'password',
        'research_line',
        'work_area'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function node()
    {
        return $this->belongsTo(Node::class);
    }
}
