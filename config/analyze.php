<?php

declare(strict_types=1);

return [
    'ga4_property_id' => env('ANALYZE_GA4_PROPERTY_ID', ''),
    // Re-fetch this many UTC days ending at D-3; old ranges remain explicitly rerunnable.
    'reimport_days' => (int) env('ANALYZE_REIMPORT_DAYS', 3),
    'max_pages_per_day' => (int) env('ANALYZE_MAX_PAGES_PER_DAY', 1000),
];
