<?php

declare(strict_types=1);

namespace Application\Console\Commands;

use Application\Jobs\Wiki\CollectVideoLinksJob;
use Illuminate\Console\Command;
use Override;
use Throwable;

class CollectVideoLinksCommand extends Command
{
    #[Override]
    protected $signature = 'video-links:collect';

    #[Override]
    protected $description = 'YouTube APIを使用して動画リンクを自動収集するJobをディスパッチする';

    public function handle(): int
    {
        try {
            CollectVideoLinksJob::dispatch();
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Video collection enqueue failed. See application logs.');

            return self::FAILURE;
        }

        $this->info('CollectVideoLinksJob dispatched.');

        return self::SUCCESS;
    }
}
