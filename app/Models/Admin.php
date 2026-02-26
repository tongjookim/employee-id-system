<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    protected $fillable = ['login_id', 'password', 'name', 'email', 'role'];
    protected $hidden = ['password'];

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }
}
