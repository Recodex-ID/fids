<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'channel',
        'language',
        'subject',
        'content',
        'html_content',
        'variables',
        'description',
        'is_active',
        'version',
        'parent_template_id',
        'variant',
        'usage_count',
        'success_rate',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
            'version' => 'integer',
            'usage_count' => 'integer',
            'success_rate' => 'decimal:2',
        ];
    }

    public function parentTemplate(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_template_id');
    }

    public function childTemplates(): HasMany
    {
        return $this->hasMany(self::class, 'parent_template_id');
    }

    /**
     * Render template with provided data
     */
    public function render(array $data = []): array
    {
        return [
            'subject' => $this->renderString($this->subject, $data),
            'content' => $this->renderString($this->content, $data),
            'html_content' => $this->html_content ? $this->renderString($this->html_content, $data) : null,
        ];
    }

    /**
     * Replace placeholders in template string
     */
    private function renderString(?string $template, array $data): ?string
    {
        if (!$template) {
            return null;
        }

        // Replace simple variables like {{variable}}
        return preg_replace_callback('/\{\{(\w+(?:\.\w+)*)\}\}/', function ($matches) use ($data) {
            $key = $matches[1];
            return data_get($data, $key, $matches[0]);
        }, $template);
    }

    /**
     * Get template variables with default values
     */
    public function getVariablesWithDefaults(): array
    {
        $defaults = [
            'passenger_name' => 'John Doe',
            'flight_number' => 'AA1001',
            'gate' => 'A15',
            'departure_time' => '14:30',
            'arrival_time' => '16:45',
            'origin_city' => 'New York',
            'destination_city' => 'Los Angeles',
            'delay_minutes' => '30',
            'new_gate' => 'B22',
            'seat_number' => '12A',
            'booking_reference' => 'ABC123',
        ];

        return array_merge($defaults, $this->variables ?? []);
    }

    /**
     * Increment usage counter
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Update success rate based on delivery success
     */
    public function updateSuccessRate(bool $success): void
    {
        $currentRate = $this->success_rate ?? 0;
        $currentCount = $this->usage_count;
        
        if ($currentCount > 0) {
            $newRate = (($currentRate * ($currentCount - 1)) + ($success ? 100 : 0)) / $currentCount;
            $this->update(['success_rate' => round($newRate, 2)]);
        }
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeByLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    public function scopeVariant($query, ?string $variant)
    {
        return $query->where('variant', $variant);
    }

    /**
     * Get the best performing template for A/B testing
     */
    public static function getBestTemplate(string $type, string $channel, string $language = 'en'): ?self
    {
        return self::active()
            ->byType($type)
            ->byChannel($channel)
            ->byLanguage($language)
            ->orderByDesc('success_rate')
            ->orderByDesc('usage_count')
            ->first();
    }

    /**
     * Get template for A/B testing (random variant)
     */
    public static function getRandomVariant(string $type, string $channel, string $language = 'en'): ?self
    {
        $templates = self::active()
            ->byType($type)
            ->byChannel($channel)
            ->byLanguage($language)
            ->whereNotNull('variant')
            ->get();

        if ($templates->isEmpty()) {
            return self::getBestTemplate($type, $channel, $language);
        }

        return $templates->random();
    }
}
