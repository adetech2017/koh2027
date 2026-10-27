<?php

namespace App\Models\Fleet;

use Database\Factories\Fleet\FleetAdminFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class FleetAdmin extends Authenticatable
{
    /** @use HasFactory<FleetAdminFactory> */
    use HasFactory;

    // super_admin manages admins; fleet_officer runs vehicles and handovers; reviewer checks evaluations
    public const ROLES = ['super_admin', 'fleet_officer', 'reviewer'];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function newFactory(): FleetAdminFactory
    {
        return FleetAdminFactory::new();
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
        ];
    }
}
