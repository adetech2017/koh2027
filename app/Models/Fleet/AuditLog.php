<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $table = 'fleet_audit_logs';

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Record an action by whoever is signed in on the fleet app (admin first, then beneficiary).
     */
    public static function record(string $action, ?Model $subject = null, array $properties = [], ?Model $actor = null): self
    {
        $actor ??= auth('fleet_admin')->user() ?? auth('fleet_beneficiary')->user();

        return static::create([
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip' => request()->ip(),
        ]);
    }
}
