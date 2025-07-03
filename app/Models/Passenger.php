<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Passenger extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'flight_id',
        'seat_number',
        'booking_reference',
    ];

    protected $hidden = [
        'phone',
    ];

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function scopeByFlight($query, int $flightId)
    {
        return $query->where('flight_id', $flightId);
    }

    public function scopeByBookingReference($query, string $reference)
    {
        return $query->where('booking_reference', $reference);
    }

    public function scopeCheckedIn($query)
    {
        return $query->whereNotNull('seat_number');
    }

    public function getInitialsAttribute(): string
    {
        $nameParts = explode(' ', $this->name);
        $initials = '';
        
        foreach ($nameParts as $part) {
            if (!empty($part)) {
                $initials .= strtoupper($part[0]);
            }
        }
        
        return $initials;
    }

    public function getFullContactInfoAttribute(): string
    {
        $contact = $this->email;
        if ($this->phone) {
            $contact .= " | {$this->phone}";
        }
        return $contact;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = ucwords(strtolower(trim($value)));
    }

    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = strtolower(trim($value));
    }
}
