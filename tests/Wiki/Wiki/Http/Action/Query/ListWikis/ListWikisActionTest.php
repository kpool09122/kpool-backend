<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Http\Action\Query\ListWikis;

use Application\Http\Action\Wiki\Wiki\Query\ListWikis\ListWikisAction;
use Application\Http\Action\Wiki\Wiki\Query\ListWikis\ListWikisRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListWikis\ListWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListWikis\ListWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListWikis\ListWikisOutput;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\CreateWiki;
use Tests\TestCase;

class ListWikisActionTest extends TestCase
{
    public function testInvokeReturnsOkResponse(): void
    {
        /** @var ListWikisRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(ListWikisRequest::class);
        $request->shouldReceive('perPage')->andReturn(10);
        $request->shouldReceive('resourceType')->andReturn('talent');
        $request->shouldReceive('keyword')->andReturn('chae');
        $request->shouldReceive('sort')->andReturn('name');
        $request->shouldReceive('order')->andReturn('asc');

        /** @var ListWikisInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(ListWikisInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(Mockery::type(ListWikisInput::class), Mockery::type(ListWikisOutput::class))
            ->andReturnUsing(static function (ListWikisInput $input, ListWikisOutput $output): void {
                $output->output([
                    new WikiListItemReadModel(
                        wikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
                        slug: 'tl-chaeyoung',
                        language: 'ko',
                        resourceType: 'talent',
                        version: 1,
                        themeColor: null,
                        name: 'Chaeyoung',
                        normalizedName: 'chaeyoung',
                        publishedAt: null,
                        updatedAt: '2026-05-01T00:00:00+00:00',
                    ),
                ], 1, 1, 1, 10);
            });

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $response = (new ListWikisAction($useCase, $logger))($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f101', $payload['wikis'][0]['wikiIdentifier']);
        $this->assertArrayNotHasKey('sections', $payload['wikis'][0]);
        $this->assertSame(10, $payload['per_page']);
    }

    #[Group('useDb')]
    public function testEndpointReturnsOkWithoutAuthentication(): void
    {
        $this->loadWikiRoutes();

        CreateWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f201',
            'talent',
            [
                'slug' => 'tl-chaeyoung',
                'language' => 'ko',
            ],
            [
                'name' => 'Chaeyoung',
                'normalized_name' => 'chaeyoung',
            ],
        );
        DB::table('wikis')
            ->where('id', '01965bb2-bcc9-7c6f-8b90-89f7f217f201')
            ->update(['updated_at' => '2026-05-01 00:00:00']);

        $response = $this->getJson('/api/wiki/wikis?resourceType=talent&keyword=chae');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('wikis.0.name', 'Chaeyoung');
        $response->assertJsonMissingPath('wikis.0.sections');
    }

    public function testEndpointReturnsValidationError(): void
    {
        $this->loadWikiRoutes();

        $response = $this->getJson('/api/wiki/wikis?resourceType=image&sort=unknown&order=sideways&perPage=0');

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['resourceType', 'sort', 'order', 'perPage']);
    }

    private function loadWikiRoutes(): void
    {
        Route::middleware(['api'])
            ->prefix('api/wiki')
            ->group(dirname(__DIR__, 7) . '/routes/wiki_private_api.php');
    }
}
