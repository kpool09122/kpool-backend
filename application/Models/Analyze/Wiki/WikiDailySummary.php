<?php

declare(strict_types=1);

namespace Application\Models\Analyze\Wiki;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-type StoredProvenance array{source: string, metric: string, grain: string, version: string, quality: list<string>, updated_at: string}
 * @property string $id
 * @property string $day
 * @property string $revision
 * @property string $wiki_identifier
 * @property string $translation_set_identifier
 * @property string $language
 * @property string $country
 * @property ?string $views
 * @property ?string $viewers
 * @property ?string $engagement_seconds
 * @property ?string $submissions
 * @property ?string $publications
 * @property ?int $engagement_denominator
 * @property array<string, StoredProvenance> $measurements
 * @property ?CarbonImmutable $updated_at
 * @property ?string $value
 * @property list<string> $quality
 * @property ?int $denominator
 */
class WikiDailySummary extends Model
{
    protected $table = 'analyze_wiki_daily_summaries';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['measurements' => 'array', 'updated_at' => 'immutable_datetime', 'views' => 'decimal:9', 'viewers' => 'decimal:9', 'engagement_seconds' => 'decimal:9', 'submissions' => 'decimal:9', 'publications' => 'decimal:9', 'engagement_denominator' => 'integer', 'value' => 'decimal:9', 'quality' => 'array', 'denominator' => 'integer'];
    }
}
