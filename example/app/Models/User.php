<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Searchable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getId(): int
    {
        return $this->getKey();
    }

    public function getName(): string
    {
        return $this->getAttribute('name');
    }

    public function getEmail(): string
    {
        return $this->getAttribute('email');
    }

    public function getEmailVerifiedAt(): ?Carbon
    {
        return $this->getAttribute('email_verified_at');
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->getAttribute($this->getCreatedAtColumn());
    }

    public function getUpdatedAt(): ?Carbon
    {
        return $this->getAttribute($this->getUpdatedAtColumn());
    }
}
