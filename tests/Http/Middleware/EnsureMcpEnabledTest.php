<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Middleware\EnsureMcpEnabled;
use Application\Mcp\McpMetadata;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class EnsureMcpEnabledTest extends TestCase
{
    public function testDisabledServerNeverReachesEndpoint(): void
    {
        config()->set('mcp_server.enabled', false);
        self::assertSame(404, $this->runMiddleware()->getStatusCode());
    }

    public function testMisconfiguredResourceIsNotPublished(): void
    {
        $this->configure();
        config()->set('mcp_oauth.resource', 'https://other.example.test');
        self::assertSame(503, $this->runMiddleware()->getStatusCode());
    }

    public function testMissingBrowserConfigurationFailsClosed(): void
    {
        $this->configure();
        config()->set('mcp_server.browser_authorization_url', null);
        self::assertSame(503, $this->runMiddleware()->getStatusCode());
    }

    public function testInternalAndExternalResourcesMustDiffer(): void
    {
        $this->configure();
        config()->set('internal_api_token.resource', 'https://api.example.test/mcp');
        self::assertSame(503, $this->runMiddleware()->getStatusCode());
    }

    public function testEnabledServerHasConsistentDiscoveryConfiguration(): void
    {
        $this->configure();
        self::assertSame(204, $this->runMiddleware()->getStatusCode());
    }

    private function configure(): void
    {
        config()->set('mcp_server.enabled', true);
        config()->set('mcp_oauth.issuer', 'https://api.example.test');
        config()->set('mcp_oauth.resource', 'https://api.example.test/mcp');
        config()->set('internal_api_token.resource', 'https://api.example.test/internal');
        config()->set('mcp_server.browser_authorization_url', 'https://app.example.test/oauth/authorize');
    }

    private function runMiddleware(): Response
    {
        return (new EnsureMcpEnabled(new McpMetadata()))->handle(Request::create('/mcp'), static fn (): Response => new Response('', 204));
    }
}
