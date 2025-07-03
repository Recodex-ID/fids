<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'passenger_id',
        'flight_id',
        'type',
        'message',
        'sent_at',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(Passenger::class);
    }

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }

    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('sent_at');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('sent_at', '>=', now()->subHours($hours));
    }

    public function getTimeAgoAttribute(): ?string
    {
        if (!$this->sent_at) {
            return null;
        }

        return $this->sent_at->diffForHumans();
    }

    public function getIsSentAttribute(): bool
    {
        return !is_null($this->sent_at);
    }

    public function setMessageAttribute($value): void
    {
        $this->attributes['message'] = trim($value);
    }

    public function markAsSent(): void
    {
        $this->update(['sent_at' => now()]);
    }
}
