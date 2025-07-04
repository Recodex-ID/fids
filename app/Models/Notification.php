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
        'status',
        'delivery_channels',
        'delivered_at',
        'failed_at',
        'failure_reason',
        'retry_count',
        'retry_at',
        'notification_id',
        'metadata',
        'priority',
        'template_name',
        'template_data',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'retry_at' => 'datetime',
            'delivery_channels' => 'array',
            'metadata' => 'array',
            'template_data' => 'array',
            'retry_count' => 'integer',
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
        $this->update([
            'sent_at' => now(),
            'status' => 'sent'
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'delivered_at' => now(),
            'status' => 'delivered'
        ]);
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'failed_at' => now(),
            'status' => 'failed',
            'failure_reason' => $reason,
            'retry_count' => $this->retry_count + 1,
            'retry_at' => $this->calculateNextRetry(),
        ]);
    }

    public function scheduleRetry(int $delayMinutes = null): void
    {
        $delay = $delayMinutes ?: $this->calculateRetryDelay();
        
        $this->update([
            'retry_at' => now()->addMinutes($delay),
            'status' => 'pending',
        ]);
    }

    private function calculateRetryDelay(): int
    {
        // Exponential backoff: 1, 2, 4, 8, 16 minutes, max 60 minutes
        return min(60, pow(2, $this->retry_count));
    }

    private function calculateNextRetry(): ?\Carbon\Carbon
    {
        if ($this->retry_count >= 5) {
            return null; // Max retries reached
        }
        
        return now()->addMinutes($this->calculateRetryDelay());
    }

    public function scopeForRetry($query)
    {
        return $query->where('status', 'failed')
                    ->where('retry_count', '<', 5)
                    ->whereNotNull('retry_at')
                    ->where('retry_at', '<=', now());
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    public function getIsRetryableAttribute(): bool
    {
        return $this->status === 'failed' && $this->retry_count < 5 && $this->retry_at && $this->retry_at <= now();
    }

    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'sent' => 'Sent',
            'delivered' => 'Delivered',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            default => 'Unknown',
        };
    }
}
