<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'about',
        'degree',
        'postgraduate',
        'expertise_area',
        'research_work',
        'profile_picture',
        'social_media',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ['password', 'remember_token',];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'social_media' => 'array', // Para manejar JSON
    ];
    
    /**
     * Relación: Un usuario puede ser líder de un solo nodo.
     */
    public function node()
    {
        return $this->hasOne(Node::class, 'leader_id');
    }

    /**
     * Relación: Un usuario puede ser miembro de UN solo nodo 
     * a la vez (a través de la tabla members).
     */
    public function member()
    {
        return $this->hasOne(Member::class, 'user_id');
    }

    /**
     * Relación: Un usuario puede crear muchas publicaciones.
     */
    public function publications()
    {
        return $this->hasMany(Publication::class, 'author_id');
    }

    /**
     * Relación: Un usuario puede crear muchos libros.
     */
    public function books()
    {
        return $this->hasMany(Book::class, 'author_id');
    }

    /**
     * Relación: Un usuario puede crear muchas series.
     */
    public function series()
    {
        return $this->hasMany(Series::class, 'author_id');
    }

    /**
     * Relación: Un usuario puede crear muchos webinars.
     */
    public function webinars()
    {
        return $this->hasMany(Webinar::class, 'author_id');
    }

    /**
     * Relación: Un usuario puede crear muchos posts de noticias.
     */
    public function newsPosts()
    {
        return $this->hasMany(NewsPost::class, 'author_id');
    }

    /**
     * Validar si un usuario tiene un rol específico.
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isNodeLeader()
    {
        return $this->role === 'node_leader';
    }

    public function isMember()
    {
        return $this->role === 'member';
    }
}
