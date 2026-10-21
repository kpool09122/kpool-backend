<?php

declare(strict_types=1);

namespace Application\Jobs\Wiki;

use Application\Http\Client\YouTubeClient\YouTubeClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Source\Wiki\VideoLinkAutoCollection\Application\UseCase\Command\CollectVideoLinks\CollectVideoLinksInterface;
use Source\Wiki\VideoLinkAutoCollection\Application\UseCase\Command\CollectVideoLinks\CollectVideoLinksOutput;
use Source\Wiki\VideoLinkAutoCollection\Domain\Exception\VideoLinkCollectionFailedException;
use Source\Wiki\VideoLinkAutoCollection\Domain\Exception\VideoSearchFailedException;
use Throwable;

class CollectVideoLinksJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 2700;

    /**
     * @throws VideoSearchFailedException
     * @throws VideoLinkCollectionFailedException
     */
    public function handle(CollectVideoLinksInterface $useCase, YouTubeClient $youTubeClient): void
    {
        if (! $youTubeClient->isConfigured()) {
            Log::warning('CollectVideoLinksJob skipped: YouTube API key is not configured');

            return;
        }

        Log::info('CollectVideoLinksJob started');

        $output = new CollectVideoLinksOutput();
        DB::transaction(static function () use ($useCase, $output): void {
            $useCase->process($output);
        });

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
