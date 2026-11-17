<?php

declare(strict_types=1);

namespace Tests\Mcp;

use Application\Mcp\McpMetadata;
use Tests\TestCase;

class McpMetadataTest extends TestCase
{
    public function testDiscoveryMatchesResourceIssuerBrowserAndTokenEndpoint(): void
    {
        config()->set('mcp_oauth.issuer', 'https://api.example.test');
        config()->set('mcp_oauth.resource', 'https://api.example.test/mcp');
        config()->set('mcp_server.browser_authorization_url', 'https://app.example.test/oauth/authorize');
        $metadata = new McpMetadata();
        self::assertSame('https://api.example.test/mcp', $metadata->resource()['resource']);
        self::assertSame(['https://api.example.test'], $metadata->resource()['authorization_servers']);
        self::assertSame('https://app.example.test/oauth/authorize', $metadata->authorizationServer()['authorization_endpoint']);
        self::assertSame('https://api.example.test/oauth/token', $metadata->authorizationServer()['token_endpoint']);
        self::assertSame(['S256'], $metadata->authorizationServer()['code_challenge_methods_supported']);
        self::assertSame(['authorization_code', 'refresh_token'], $metadata->authorizationServer()['grant_types_supported']);
        self::assertArrayNotHasKey('registration_endpoint', $metadata->authorizationServer());
        self::assertArrayNotHasKey('client_id_metadata_document_supported', $metadata->authorizationServer());
    }
}
