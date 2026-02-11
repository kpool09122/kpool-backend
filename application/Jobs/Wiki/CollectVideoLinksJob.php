<?php

declare(strict_types=1);

namespace Application\Jobs\Wiki;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Source\Wiki\VideoLinkAutoCollection\Application\UseCase\Command\CollectVideoLinks\CollectVideoLinksInterface;
use Source\Wiki\VideoLinkAutoCollection\Application\UseCase\Command\CollectVideoLinks\CollectVideoLinksOutput;
use Throwable;

class CollectVideoLinksJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 2700;

    public function handle(CollectVideoLinksInterface $useCase): void
    {
        Log::info('CollectVideoLinksJob started');

        $output = new CollectVideoLinksOutput();
        $useCase->process($output);

        if ($output->processed) {
            Log::info('CollectVideoLinksJob completed', [
                'resource_type' => $output->resourceType?->value,
                'wiki_id' => $output->wikiIdentifier !== null ? (string) $output->wikiIdentifier : null,
                'collected_count' => $output->collectedCount,
            ]);
        } else {
            Log::info('CollectVideoLinksJob completed without processing', [
                'message' => $output->message,
            ]);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('CollectVideoLinksJob failed', [
            'message' => $exception->getMessage(),
        ]);
    }
}
