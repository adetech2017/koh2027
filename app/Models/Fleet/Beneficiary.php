<?php

namespace App\Models\Fleet;

use Database\Factories\Fleet\BeneficiaryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Someone a campaign vehicle is handed to. Signs in with a WhatsApp code, so has no password.
 */
#[Fillable(['full_name', 'phone', 'alt_phone', 'email', 'address', 'lga', 'ward', 'directorate', 'sub_directorate', 'id_type', 'id_number', 'notes'])]
#[Hidden(['remember_token'])]
class Beneficiary extends Authenticatable
{
    /** @use HasFactory<BeneficiaryFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'fleet_beneficiaries';

    // Beneficiaries never use "remember me"; the table has no remember_token column
    protected $rememberTokenName = '';

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    protected static function newFactory(): BeneficiaryFactory
    {
        return BeneficiaryFactory::new();
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(Assignment::class)->where('status', 'active')->latestOfMany();
    }

    /**
     * What the beneficiary app sees about its own user.
     */
    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'lga' => $this->lga,
            'ward' => $this->ward,
        ];
    }

    /**
     * The full record, for fleet admins.
     */
    public function toAdminApi(): array
    {
        return $this->toApi() + [
            'alt_phone' => $this->alt_phone,
            'email' => $this->email,
            'address' => $this->address,
            'directorate' => $this->directorate,
            'sub_directorate' => $this->sub_directorate,
            'id_type' => $this->id_type,
            'id_number' => $this->id_number,
            'notes' => $this->notes,
            'active' => $this->isActive(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
