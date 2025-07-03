<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Flight extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'flight_number',
        'airline_id',
        'origin_airport_id',
        'destination_airport_id',
        'scheduled_departure',
        'scheduled_arrival',
        'actual_departure',
        'actual_arrival',
        'gate',
        'status',
        'aircraft_type',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'scheduled_departure' => 'datetime',
            'scheduled_arrival' => 'datetime',
            'actual_departure' => 'datetime',
            'actual_arrival' => 'datetime',
        ];
    }

    public function airline(): BelongsTo
    {
        return $this->belongsTo(Airline::class);
    }

    public function originAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'origin_airport_id');
    }

    public function destinationAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'destination_airport_id');
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(Passenger::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['scheduled', 'boarding', 'departed']);
    }

    public function scopeDelayed($query)
    {
        return $query->where('status', 'delayed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_departure', today());
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByAirline($query, int $airlineId)
    {
        return $query->where('airline_id', $airlineId);
    }

    public function scopeBoarding($query)
    {
        return $query->where('status', 'boarding');
    }

    public function scopeDeparted($query)
    {
        return $query->where('status', 'departed');
    }

    public function getIsDelayedAttribute(): bool
    {
        return $this->status === 'delayed' ||
               ($this->actual_departure && $this->actual_departure->gt($this->scheduled_departure));
    }

    public function getDurationAttribute(): ?int
    {
        if (!$this->scheduled_departure || !$this->scheduled_arrival) {
            return null;
        }

        return $this->scheduled_departure->diffInMinutes($this->scheduled_arrival);
    }

    public function getStatusFormattedAttribute(): string
    {
        return match ($this->status) {
            'scheduled' => 'Scheduled',
            'boarding' => 'Boarding',
            'departed' => 'Departed',
            'delayed' => 'Delayed',
            'cancelled' => 'Cancelled',
            'arrived' => 'Arrived',
            default => ucfirst($this->status),
        };
    }

    public function getDepartureLocalAttribute(): ?string
    {
        if (!$this->scheduled_departure || !$this->originAirport) {
            return null;
        }

        return $this->scheduled_departure
            ->setTimezone($this->originAirport->timezone)
            ->format('Y-m-d H:i:s T');
    }

    public function getArrivalLocalAttribute(): ?string
    {
        if (!$this->scheduled_arrival || !$this->destinationAirport) {
            return null;
        }

        return $this->scheduled_arrival
            ->setTimezone($this->destinationAirport->timezone)
            ->format('Y-m-d H:i:s T');
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = strtolower($value);
    }
}
