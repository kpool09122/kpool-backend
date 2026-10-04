<?php

declare(strict_types=1);

namespace Tests\Jobs;

use Application\Http\Client\YouTubeClient\YouTubeClient;
use Application\Jobs\Wiki\CollectVideoLinksJob;
use Mockery;
use Source\Wiki\VideoLinkAutoCollection\Application\UseCase\Command\CollectVideoLinks\CollectVideoLinksInterface;
use Source\Wiki\VideoLinkAutoCollection\Application\UseCase\Command\CollectVideoLinks\CollectVideoLinksOutputPort;
use Tests\TestCase;

class CollectVideoLinksJobTest extends TestCase
{
    public function testMissingApiKeySkipsCollectionBeforeExistingLinksCanBeModified(): void
    {
        config(['services.youtube.api_key' => null]);
        $useCase = Mockery::mock(CollectVideoLinksInterface::class);
        $useCase->shouldNotReceive('process');
        $this->app()->instance(CollectVideoLinksInterface::class, $useCase);
        self::assertFalse($this->app()->make(YouTubeClient::class)->isConfigured());

        $this->app()->call([new CollectVideoLinksJob(), 'handle']);
    }

    public function testConfiguredApiKeyRunsCollection(): void
    {
        config(['services.youtube.api_key' => 'test-api-key']);
        $useCase = Mockery::mock(CollectVideoLinksInterface::class);
        $useCase->shouldReceive('process')->once()
            ->with(Mockery::type(CollectVideoLinksOutputPort::class));
        $this->app()->instance(CollectVideoLinksInterface::class, $useCase);

        $this->app()->call([new CollectVideoLinksJob(), 'handle']);
    }
}
