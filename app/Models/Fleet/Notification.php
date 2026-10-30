<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'fleet_notifications';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class)->withTrashed();
    }

    public function messageTemplate(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(FleetAdmin::class, 'sent_by');
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->messageTemplate?->title,
            'body' => $this->body,
            'status' => $this->status,
            'error' => $this->error,
            'sent_by' => $this->sender?->name,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            ...($this->relationLoaded('beneficiary') ? [
                'beneficiary' => ['id' => $this->beneficiary->id, 'full_name' => $this->beneficiary->full_name, 'phone' => $this->beneficiary->phone],
            ] : []),
        ];
    }
}
