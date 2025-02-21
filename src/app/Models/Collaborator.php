<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collaborator extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'reason',
        'subscription_status',
        'status',
    ];

    protected $casts = [
        'subscription_status' => 'boolean',
        'status' => 'string',
    ];
}
