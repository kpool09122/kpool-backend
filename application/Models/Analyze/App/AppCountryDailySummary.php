<?php

declare(strict_types=1);

namespace Application\Models\Analyze\App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $day
 * @property string $country
 * @property string $application_users
 * @property string $registered_users
 */
class AppCountryDailySummary extends Model
{
    protected $table = 'analyze_app_country_daily_summaries';
    protected $guarded = [];
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['application_users' => 'decimal:9', 'registered_users' => 'decimal:9'];
    }
}
