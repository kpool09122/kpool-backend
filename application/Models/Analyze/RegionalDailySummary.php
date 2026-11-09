<?php

declare(strict_types=1);

namespace Application\Models\Analyze;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $context
 * @property string $day
 * @property string $country
 * @property string $region
 * @property string $wiki_identifier
 * @property string $translation_set_identifier
 * @property string $language
 * @property string $source
 * @property string $definition_version
 * @property string $metric
 * @property ?string $value
 * @property ?int $denominator
 * @property list<string> $quality
 * @property string $revision
 * @property string $updated_at
 */
class RegionalDailySummary extends Model
{
    protected $table = 'analyze_regional_daily_summaries';
    protected $guarded = [];
    public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = null;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['value' => 'decimal:9', 'denominator' => 'integer', 'quality' => 'array'];
    }
}
