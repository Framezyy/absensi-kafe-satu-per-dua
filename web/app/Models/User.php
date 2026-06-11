<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = ['name', 'username', 'email', 'password', 'role', 'status'];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array {
        return ['password' => 'hashed', 'email_verified_at' => 'datetime'];
    }
    public function karyawan() { return $this->hasOne(Karyawan::class); }
    public function isAdmin() { return $this->role === 'admin'; }
}