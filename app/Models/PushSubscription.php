<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'passenger_id',
        'endpoint',
        'p256dh_key',
        'auth_key',
        'user_agent',
        'is_active',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(Passenger::class);
    }

    /**
     * Get subscription data for web push
     */
    public function getSubscriptionData(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'keys' => [
                'p256dh' => $this->p256dh_key,
                'auth' => $this->auth_key,
            ],
        ];
    }

    /**
     * Update last used timestamp
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Deactivate subscription
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPassenger($query, int $passengerId)
    {
        return $query->where('passenger_id', $passengerId);
    }
}
