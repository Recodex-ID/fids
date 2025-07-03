<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Airport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'city',
        'country',
        'timezone',
    ];

    protected $hidden = [];

    public function departures(): HasMany
    {
        return $this->hasMany(Flight::class, 'origin_airport_id');
    }

    public function arrivals(): HasMany
    {
        return $this->hasMany(Flight::class, 'destination_airport_id');
    }

    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', strtoupper($code));
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->name}, {$this->city}";
    }

    public function getLocalTimeAttribute(): string
    {
        return Carbon::now($this->timezone)->format('Y-m-d H:i:s T');
    }

    public function setTimezoneAttribute($value): void
    {
        $this->attributes['timezone'] = $value;
    }
}
