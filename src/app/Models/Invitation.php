<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'role_type',
        'node_type',
        'node_id',
        'token',
        'status',
        'sent_date',
        'accepted_date',
        'expiration_date'
    ];

    protected $casts = [
        'sent_date' => 'datetime',
        'accepted_date' => 'datetime',
        'expiration_date' => 'datetime',
    ];

    /**
     * Relación con el nodo (si aplica).
     * Un nodo puede tener muchas invitaciones de miembros.
     */
    public function node()
    {
        return $this->belongsTo(Node::class, 'node_id');
    }

    /**
     * Verifica si la invitación ha sido aceptada.
     */
    public function isAccepted()
    {
        return $this->status === 'aceptada';
    }

    /**
     * Verifica si la invitación está pendiente.
     */
    public function isPending()
    {
        return $this->status === 'pendiente';
    }

    /**
     * Verifica si la invitación ha expirado.
     */
    public function isExpired()
    {
        return $this->status === 'expirada';
    }
}
