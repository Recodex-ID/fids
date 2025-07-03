<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Airline extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'logo_url',
    ];

    protected $hidden = [];

    public function flights(): HasMany
    {
        return $this->hasMany(Flight::class);
    }

    public function scopeActive($query)
    {
        return $query->whereHas('flights', function ($query) {
            $query->whereIn('status', ['scheduled', 'boarding', 'departed']);
        });
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', strtoupper($code));
    }

    public function getFormattedNameAttribute(): string
    {
        return "{$this->name} ({$this->code})";
    }
}
