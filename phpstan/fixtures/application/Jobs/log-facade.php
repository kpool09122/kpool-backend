<?php

namespace Fixtures\Jobs;

use Illuminate\Support\Facades\Log;

function failed(): void
{
    Log::error('allowed in Jobs');
}
