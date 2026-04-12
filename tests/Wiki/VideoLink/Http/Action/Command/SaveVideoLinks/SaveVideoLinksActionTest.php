<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Http\Action\Command\SaveVideoLinks;

use Application\Http\Action\Wiki\VideoLink\Command\SaveVideoLinks\SaveVideoLinksAction;
use Application\Http\Action\Wiki\VideoLink\Command\SaveVideoLinks\SaveVideoLinksRequest;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks\SaveVideoLinksInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks\SaveVideoLinksInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SaveVideoLinksActionTest extends TestCase
{
    public function testInvokeReturnsNoContentResponse(): void
    {
        /** @var SaveVideoLinksRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(SaveVideoLinksRequest::class);
        $request->shouldReceive('principalId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('resourceType')->andReturn('talent');
        $request->shouldReceive('wikiIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('videoLinks')->andReturn([
            [
                'url' => 'https://www.youtube.com/watch?v=test123',
                'videoUsage' => 'music_video',
                'title' => 'Test Video',
                'displayOrder' => 1,
                'thumbnailUrl' => 'https://img.youtube.com/vi/test123/0.jpg',
                'publishedAt' => '2024-01-01',
            ],
        ]);
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var SaveVideoLinksInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(SaveVideoLinksInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(Mockery::type(SaveVideoLinksInput::class));

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $action = new SaveVideoLinksAction($useCase, $logger);

        $response = $action($request);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function testInvokeReturnsUnprocessableEntityResponseWhenInvalidArgumentException(): void
    {
        /** @var SaveVideoLinksRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(SaveVideoLinksRequest::class);
        $request->shouldReceive('principalId')->andReturn('invalid-uuid');
        $request->shouldReceive('resourceType')->andReturn('talent');
        $request->shouldReceive('wikiIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('videoLinks')->andReturn([
            [
                'url' => 'https://www.youtube.com/watch?v=test123',
                'videoUsage' => 'music_video',
                'title' => 'Test Video',
                'displayOrder' => 1,
                'thumbnailUrl' => null,
                'publishedAt' => null,
            ],
        ]);
        $request->shouldReceive('language')->andReturn('en');

        /** @var SaveVideoLinksInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(SaveVideoLinksInterface::class);
        $useCase->shouldNotReceive('process');

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new SaveVideoLinksAction($useCase, $logger);

        $response = $action($request);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }
}
