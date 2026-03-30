<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table      = 'PRINUSERS';
    protected $primaryKey = 'USER';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;

    protected $fillable = ['USER', 'PASSWORD', 'PROFILE'];
    protected $hidden   = ['PASSWORD'];

    public function getAuthIdentifierName(): string
    {
        return 'USER';
    }

    public function getAuthPassword(): string
    {
        return $this->PASSWORD;
    }

    public function getRememberTokenName(): ?string
    {
        return null;
    }
}
