<?php

declare(strict_types=1);

namespace Tests\Jobs;

use Application\Http\Client\YouTubeClient\YouTubeClient;
use Application\Jobs\Wiki\CollectVideoLinksJob;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Wiki\VideoLink\Application\UseCase\Command\CollectVideoLinks\CollectVideoLinksInterface;
use Source\Wiki\VideoLink\Application\UseCase\Command\CollectVideoLinks\CollectVideoLinksOutputPort;
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

    #[Group('useDb')]
    public function testConfiguredApiKeyRunsCollection(): void
    {
        config(['services.youtube.api_key' => 'test-api-key']);
        $useCase = Mockery::mock(CollectVideoLinksInterface::class);
        $level = DB::transactionLevel();
        $useCase->shouldReceive('process')->once()
            ->with(Mockery::type(CollectVideoLinksOutputPort::class))
            ->andReturnUsing(function () use ($level): void {
                $this->assertSame($level + 1, DB::transactionLevel());
            });
        $this->app()->instance(CollectVideoLinksInterface::class, $useCase);

        $this->app()->call([new CollectVideoLinksJob(), 'handle']);
        $this->assertSame($level, DB::transactionLevel());
    }
}
