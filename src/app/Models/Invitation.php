<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'role_type',
        'node_id',
        'reserved_code',
        'status',
        'sent_date',
        'accepted_date',
        'expired_date'
    ];

    public function node()
    {
        return $this->belongsTo(Node::class);
    }
}
