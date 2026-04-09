<?php

declare(strict_types=1);

namespace Application\Console\Commands;

use Application\Jobs\Wiki\CollectVideoLinksJob;
use Illuminate\Console\Command;

class CollectVideoLinksCommand extends Command
{
    #[\Override]
    protected $signature = 'video-links:collect';

    #[\Override]
    protected $description = 'YouTube APIを使用して動画リンクを自動収集するJobをディスパッチする';

    public function handle(): int
    {
        CollectVideoLinksJob::dispatch();

        $this->info('CollectVideoLinksJob dispatched.');

        return self::SUCCESS;
    }
}
