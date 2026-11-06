<?php

declare(strict_types=1);

namespace Application\Models\Analyze\App;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-type StoredProvenance array{source: string, metric: string, grain: string, version: string, quality: list<string>, updated_at: string}
 * @property string $id
 * @property string $day
 * @property string $revision
 * @property ?string $application_users
 * @property ?string $registered_users
 * @property ?CarbonImmutable $registered_users_snapshot_at
 * @property array<string, StoredProvenance> $measurements
 * @property ?CarbonImmutable $updated_at
 * @property ?string $value
 * @property list<string> $quality
 * @property ?int $denominator
 */
class AppDailySummary extends Model
{
    protected $table = 'analyze_app_daily_summaries';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['measurements' => 'array', 'updated_at' => 'immutable_datetime', 'application_users' => 'decimal:9', 'registered_users' => 'decimal:9', 'registered_users_snapshot_at' => 'immutable_datetime', 'value' => 'decimal:9', 'quality' => 'array', 'denominator' => 'integer'];
    }
}
