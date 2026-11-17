<?php

declare(strict_types=1);

namespace Application\Mcp;

use LogicException;
use Source\Identity\Domain\ValueObject\AuthorizationScope;

readonly class McpMetadata
{
    public function assertConfigured(): void
    {
        $issuer = config()->string('mcp_oauth.issuer');
        $resource = config()->string('mcp_oauth.resource');
        $browser = config()->string('mcp_server.browser_authorization_url');
        foreach ([$issuer, $resource, $browser] as $url) {
            if (! filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_USER) !== null
                || parse_url($url, PHP_URL_QUERY) !== null || parse_url($url, PHP_URL_FRAGMENT) !== null
                || (parse_url($url, PHP_URL_SCHEME) !== 'https' && ! app()->environment(['local', 'testing']))) {
                throw new LogicException('Invalid MCP endpoint configuration.');
            }
        }
        if (parse_url($issuer, PHP_URL_PATH) !== null || $resource !== $issuer . '/mcp'
            || config()->string('internal_api_token.resource') === $resource) {
            throw new LogicException('Invalid MCP resource configuration.');
        }
    }

    /** @return array{resource: string, authorization_servers: list<string>, scopes_supported: list<string>, bearer_methods_supported: list<string>} */
    public function resource(): array
    {
        return ['resource' => config()->string('mcp_oauth.resource'), 'authorization_servers' => [config()->string('mcp_oauth.issuer')],
            'scopes_supported' => $this->scopes(), 'bearer_methods_supported' => ['header']];
    }

    /** @return array<string, string|list<string>> */
    public function authorizationServer(): array
    {
        return ['issuer' => config()->string('mcp_oauth.issuer'),
            'authorization_endpoint' => config()->string('mcp_server.browser_authorization_url'),
            'token_endpoint' => config()->string('mcp_oauth.issuer') . '/oauth/token',
            'response_types_supported' => ['code'], 'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'code_challenge_methods_supported' => ['S256'], 'token_endpoint_auth_methods_supported' => ['none', 'client_secret_post', 'client_secret_basic'],
            'scopes_supported' => $this->scopes()];
    }

    /** @return list<string> */
    private function scopes(): array
    {
        return array_map(static fn (AuthorizationScope $scope): string => $scope->value, AuthorizationScope::cases());
    }
}
