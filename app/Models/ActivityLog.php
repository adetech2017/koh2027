<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    const UPDATED_AT = null;

    #[\Illuminate\Database\Eloquent\Attributes\Fillable(['user_id', 'action', 'subject_type', 'subject_id', 'properties', 'ip_address'])]
    protected $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'properties', 'ip_address'];

    #[\Illuminate\Database\Eloquent\Attributes\Casts(['properties' => 'json'])]
    protected $casts = ['properties' => 'json'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function record(string $action, Model $subject, array $properties = [], ?User $user = null, ?string $ip = null): self
    {
        return self::create([
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->id,
            'properties' => $properties ?: null,
            'ip_address' => $ip ?? request()?->ip(),
        ]);
    }
}
