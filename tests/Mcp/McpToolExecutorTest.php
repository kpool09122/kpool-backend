<?php

declare(strict_types=1);

namespace Tests\Mcp;

use Application\Mcp\AccountResultProjector;
use Application\Mcp\McpApiClient;
use Application\Mcp\McpToolExecutor;
use Application\Mcp\OpenApiToolRegistry;
use DateTimeImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\MockInterface;
use Source\Identity\Application\Service\InternalApiToken;
use Source\Identity\Application\Service\InternalApiTokenServiceInterface;
use Tests\TestCase;

class McpToolExecutorTest extends TestCase
{
    public function testImageMetadataReplacementUsesPutWithTheCompleteBody(): void
    {
        config()->set('mcp_server.api_base_url', 'https://api.example.test');
        $imageId = '019a8fa0-3f20-7000-8000-000000000001';
        $url = 'https://api.example.test/api/v1/wiki/image/' . $imageId . '/metadata';
        $metadata = ['sourceUrl' => null, 'sourceName' => 'Source', 'author' => 'Author',
            'license' => 'CC-BY-4.0', 'altText' => null, 'displayOrder' => 0];
        $response = ['imageIdentifier' => $imageId];
        Http::preventStrayRequests();
        Http::fake([$url => Http::response($response)]);
        /** @var InternalApiTokenServiceInterface&MockInterface $internalApiTokenService */
        $internalApiTokenService = Mockery::mock(InternalApiTokenServiceInterface::class);
        $internalApiTokenService->shouldReceive('issue')->once()->with('source-token')
            ->andReturn(new InternalApiToken('internal-secret', new DateTimeImmutable('+1 minute')));
        $tool = app(OpenApiToolRegistry::class)->find('update_wiki_image_metadata');
        self::assertNotNull($tool);
        $executor = new McpToolExecutor(new McpApiClient($internalApiTokenService), new AccountResultProjector());

        self::assertSame($response, $executor->execute($tool, (object) ['imageId' => $imageId, ...$metadata], 'source-token'));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request->url() === $url
            && $request->data() === $metadata
            && $request->hasHeader('Authorization', 'Bearer internal-secret'));
        Http::assertSentCount(1);
    }

    public function testSubmitCallsOnlyWikiApiAndOmitsUntrustedVisitorHeaders(): void
    {
        config()->set('mcp_server.api_base_url', 'https://api.example.test');
        Http::preventStrayRequests();
        Http::fake([
            'https://api.example.test/api/v1/wiki/wiki/*/submit' => Http::response(['wikiIdentifier' => 'draft-id', 'status' => 'under_review'], 201),
        ]);
        /** @var InternalApiTokenServiceInterface&MockInterface $internalApiTokenService */
        $internalApiTokenService = Mockery::mock(InternalApiTokenServiceInterface::class);
        $internalApiTokenService->shouldReceive('issue')->once()->with('source-token')->andReturn(new InternalApiToken('internal-secret', new DateTimeImmutable('+1 minute')));
        $executor = new McpToolExecutor(new McpApiClient($internalApiTokenService), new AccountResultProjector());
        $tool = app(OpenApiToolRegistry::class)->find('submit_wiki_draft');
        self::assertNotNull($tool);
        $result = $executor->execute($tool, (object) ['wikiId' => '019a8fa0-3f20-7000-8000-000000000001', 'resourceType' => 'group'], 'source-token');
        self::assertSame(['wikiIdentifier' => 'draft-id', 'status' => 'under_review'], $result);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->data() === ['resourceType' => 'group']
            && $request->hasHeader('Authorization', 'Bearer internal-secret')
            && ! $request->hasHeader('X-Kpool-Visitor-Country')
            && ! $request->hasHeader('X-Kpool-Visitor-Region')
            && ! $request->hasHeader('X-Kpool-Visitor-Timestamp')
            && ! $request->hasHeader('X-Kpool-Visitor-Signature'));
        Http::assertSentCount(1);
    }
}
