<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'passenger_id',
        'email_enabled',
        'sms_enabled',
        'push_enabled',
        'in_app_enabled',
        'flight_status_changes',
        'gate_changes',
        'boarding_calls',
        'delays',
        'cancellations',
        'schedule_changes',
        'check_in_reminders',
        'baggage_updates',
        'boarding_call_advance_minutes',
        'delay_notification_threshold',
        'quiet_hours_start',
        'quiet_hours_end',
        'notification_frequency',
        'language',
        'timezone',
    ];

    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'in_app_enabled' => 'boolean',
            'flight_status_changes' => 'boolean',
            'gate_changes' => 'boolean',
            'boarding_calls' => 'boolean',
            'delays' => 'boolean',
            'cancellations' => 'boolean',
            'schedule_changes' => 'boolean',
            'check_in_reminders' => 'boolean',
            'baggage_updates' => 'boolean',
            'boarding_call_advance_minutes' => 'integer',
            'delay_notification_threshold' => 'integer',
            'quiet_hours_start' => 'datetime:H:i',
            'quiet_hours_end' => 'datetime:H:i',
        ];
    }

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(Passenger::class);
    }

    /**
     * Get enabled notification channels for this passenger
     */
    public function getEnabledChannelsAttribute(): array
    {
        $channels = [];
        
        if ($this->email_enabled) $channels[] = 'mail';
        if ($this->sms_enabled) $channels[] = 'sms';
        if ($this->push_enabled) $channels[] = 'push';
        if ($this->in_app_enabled) $channels[] = 'database';
        
        return $channels;
    }

    /**
     * Check if a specific notification type is enabled
     */
    public function isNotificationTypeEnabled(string $type): bool
    {
        return match ($type) {
            'flight_status_change' => $this->flight_status_changes,
            'gate_change' => $this->gate_changes,
            'boarding_call' => $this->boarding_calls,
            'delay' => $this->delays,
            'cancellation' => $this->cancellations,
            'schedule_change' => $this->schedule_changes,
            'check_in_reminder' => $this->check_in_reminders,
            'baggage_update' => $this->baggage_updates,
            default => false,
        };
    }

    /**
     * Check if notifications should be sent at the current time (quiet hours)
     */
    public function isInQuietHours(): bool
    {
        if (!$this->quiet_hours_start || !$this->quiet_hours_end) {
            return false;
        }

        $now = now($this->timezone ?: config('app.timezone'))->format('H:i');
        $start = $this->quiet_hours_start->format('H:i');
        $end = $this->quiet_hours_end->format('H:i');

        if ($start <= $end) {
            return $now >= $start && $now <= $end;
        } else {
            // Quiet hours span midnight
            return $now >= $start || $now <= $end;
        }
    }

    /**
     * Get channels for a specific notification type
     */
    public function getChannelsForNotificationType(string $type): array
    {
        if (!$this->isNotificationTypeEnabled($type)) {
            return [];
        }

        if ($this->isInQuietHours()) {
            // During quiet hours, only allow non-intrusive channels
            return array_intersect($this->enabled_channels, ['database']);
        }

        return $this->enabled_channels;
    }

    /**
     * Create default preferences for a passenger
     */
    public static function createDefaultForPassenger(Passenger $passenger): self
    {
        return self::create([
            'passenger_id' => $passenger->id,
            'timezone' => $passenger->flight->destination_airport->timezone ?? config('app.timezone'),
        ]);
    }
}
