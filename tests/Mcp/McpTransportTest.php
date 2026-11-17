<?php

declare(strict_types=1);

namespace Tests\Mcp;

use Application\Mcp\AccountApiAdapter;
use Application\Mcp\McpTransport;
use DateTimeImmutable;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\AssertableJsonString;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Identity\Application\Exception\InvalidAuthorizationTokenException;
use Source\Identity\Application\Service\AuthorizationTokenVerifierInterface;
use Source\Identity\Application\Service\InternalApiToken;
use Source\Identity\Application\Service\InternalApiTokenServiceInterface;
use Source\Identity\Application\Service\ValidatedAuthorizationToken;
use Source\Identity\Domain\Entity\AuthorizationConnection;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class McpTransportTest extends TestCase
{
    public function testSwitchAccountAndOriginalSelectionGoThroughHttpWithoutReturningTokens(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://api.example.test/api/v1/account/accounts/switch' => Http::response([])]);
        foreach ([(string) Str::uuid7(), null] as $delegationIdentifier) {
            $response = $this->send(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'switch_account', 'arguments' => ['delegationIdentifier' => $delegationIdentifier]]], issueToken: true);
            self::assertSame(200, $response->getStatusCode());
            (new AssertableJsonString((string) $response->getContent()))->assertPath('result.structuredContent.switched', true);
            self::assertStringNotContainsString('internal-secret', (string) $response->getContent());
            Http::assertSent(fn (ClientRequest $request): bool => $request['delegationIdentifier'] === $delegationIdentifier && $request->hasHeader('Authorization', 'Bearer internal-secret'));
        }
        Http::assertSentCount(2);
    }

    /** @return list<array{int, string}> */
    public static function apiErrors(): array
    {
        return [[401, 'authentication_required'], [403, 'forbidden'], [404, 'not_found'], [409, 'conflict'], [422, 'validation_failed'], [429, 'rate_limited'], [500, 'api_unavailable']];
    }

    #[DataProvider('apiErrors')]
    public function testApiErrorsAreDistinguishedAndNeverRetriedOrForwardedRaw(int $status, string $code): void
    {
        Http::fake(['https://api.example.test/api/v1/identity/auth/me' => Http::response(['detail' => 'internal-secret refresh-secret trace'], $status, ['Retry-After' => '30'])]);
        $response = $this->send(['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call', 'params' => ['name' => 'get_account_context']], issueToken: true);
        self::assertStringNotContainsString('secret', (string) $response->getContent());
        if ($status === 401) {
            self::assertSame(401, $response->getStatusCode());
            self::assertTrue($response->headers->has('WWW-Authenticate'));
        } else {
            self::assertSame(200, $response->getStatusCode());
            $json = new AssertableJsonString((string) $response->getContent());
            $json->assertPath('result.isError', true);
            $json->assertPath('result.structuredContent.code', $code);
            $json->assertPath('result.structuredContent.status', $status === 500 ? 502 : $status);
            $json->assertPath('result.structuredContent.retryAfter', $status === 429 ? 30 : null);
        }
        Http::assertSentCount(1);
    }

    public function testApiInsufficientScopeReturnsAuthorizationChallengeRatherThanBusinessError(): void
    {
        Http::fake(['https://api.example.test/api/v1/identity/auth/me' => Http::response(['code' => 'insufficient_scope'], 403)]);
        $response = $this->send(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'get_account_context']], issueToken: true);
        self::assertSame(403, $response->getStatusCode());
        self::assertStringContainsString('insufficient_scope', (string) $response->headers->get('WWW-Authenticate'));
    }

    public function testToolNotificationCannotSwitchAnAccount(): void
    {
        Http::preventStrayRequests();
        $response = $this->send(['jsonrpc' => '2.0', 'method' => 'tools/call', 'params' => ['name' => 'switch_account', 'arguments' => ['delegationIdentifier' => null]]]);
        self::assertSame(202, $response->getStatusCode());
        self::assertSame('', $response->getContent());
        Http::assertNothingSent();
    }

    public function testInvalidEnvelopesAndHttpNegotiationFailWithoutApiCalls(): void
    {
        Http::preventStrayRequests();
        foreach (['{', '[]', '{"jsonrpc":"2.0","method":"ping","id":null}', '{"jsonrpc":"2.0","method":"ping","id":{}}', '{"jsonrpc":"2.0","method":"tools/call","id":1,"params":[]}'] as $raw) {
            self::assertSame(400, $this->send([], raw: $raw)->getStatusCode());
        }
        self::assertSame(406, $this->send(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], ['HTTP_ACCEPT' => 'application/json'])->getStatusCode());
        self::assertSame(415, $this->send([], ['CONTENT_TYPE' => 'text/plain'])->getStatusCode());
        self::assertSame(413, $this->send([], raw: str_repeat('x', 65537))->getStatusCode());
        Http::assertNothingSent();
    }

    public function testSameTransportRevalidatesTokenOnEveryRequest(): void
    {
        config()->set('mcp_server.enabled', true);
        config()->set('mcp_oauth.issuer', 'https://auth.example.test');
        /** @var AuthorizationTokenVerifierInterface&MockInterface $verifier */
        $verifier = Mockery::mock(AuthorizationTokenVerifierInterface::class);
        $verifier->shouldReceive('verify')->once()->ordered()->with('valid-token')->andReturn($this->verifiedToken());
        $verifier->shouldReceive('verify')->once()->ordered()->with('valid-token')->andThrow(new InvalidAuthorizationTokenException());
        /** @var InternalApiTokenServiceInterface&MockInterface $internalApiTokenService */
        $internalApiTokenService = Mockery::mock(InternalApiTokenServiceInterface::class);
        $internalApiTokenService->shouldNotReceive('issue');
        $transport = new McpTransport($verifier, new AccountApiAdapter($internalApiTokenService));
        $request = Request::create('/mcp', 'POST', server: ['HTTP_AUTHORIZATION' => 'Bearer valid-token', 'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json, text/event-stream'], content: '{"jsonrpc":"2.0","id":1,"method":"tools/list"}');
        self::assertSame(200, $transport->handle($request)->getStatusCode());
        self::assertSame(401, $transport->handle($request)->getStatusCode());
    }

    public function testInvalidInitializeParamsAreProtocolErrors(): void
    {
        $json = new AssertableJsonString((string) $this->send(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => '2025-06-18', 'capabilities' => 'invalid', 'clientInfo' => 'invalid']])->getContent());
        $json->assertPath('error.code', -32602);
    }

    public function testInitializationToolDiscoveryPingAndNotifications(): void
    {
        $response = $this->send(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => '2025-06-18', 'capabilities' => (object) [], 'clientInfo' => ['name' => 'test', 'version' => '1']]]);
        self::assertSame(200, $response->getStatusCode());
        $body = new AssertableJsonString((string) $response->getContent());
        $body->assertPath('result.protocolVersion', '2025-06-18');
        $body->assertPath('result.serverInfo.name', 'k-pool');
        $list = $this->send(['jsonrpc' => '2.0', 'id' => 'list', 'method' => 'tools/list']);
        $body = new AssertableJsonString((string) $list->getContent());
        $body->assertPath('result.tools.0.name', 'get_account_context');
        $body->assertPath('result.tools.1.name', 'switch_account');
        $body->assertPath('result.tools.0.securitySchemes', [['type' => 'oauth2', 'scopes' => []]]);
        self::assertSame(202, $this->send(['jsonrpc' => '2.0', 'method' => 'notifications/initialized'])->getStatusCode());
        self::assertSame(200, $this->send(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'ping'])->getStatusCode());
    }

    public function testInvalidToolArgumentsNeverIssueInternalTokenOrInvokeApi(): void
    {
        Http::preventStrayRequests();
        foreach ([['name' => 'unknown'], ['name' => 'switch_account', 'arguments' => []], ['name' => 'switch_account', 'arguments' => ['delegationIdentifier' => null, 'url' => 'https://evil.test']], ['name' => 'switch_account', 'arguments' => ['delegationIdentifier' => 'not-uuid']], ['name' => 'get_account_context', 'arguments' => ['token' => 'secret']]] as $params) {
            $body = new AssertableJsonString((string) $this->send(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => $params])->getContent());
            $body->assertPath('error.code', -32602);
        }
        Http::assertNothingSent();
    }

    public function testInvalidOriginAndUnsupportedProtocolAreRejected(): void
    {
        self::assertSame(403, $this->send(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], ['HTTP_ORIGIN' => 'https://evil.test'])->getStatusCode());
        self::assertSame(400, $this->send(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], ['HTTP_MCP_PROTOCOL_VERSION' => 'invalid'])->getStatusCode());
        self::assertSame(405, $this->send([], [], 'GET')->getStatusCode());
    }

    /** @param array<string, mixed> $message
     * @param array<string, string> $server
     */
    private function send(array $message, array $server = [], string $method = 'POST', bool $issueToken = false, ?string $raw = null): Response
    {
        config()->set('mcp_server.enabled', true);
        config()->set('mcp_oauth.issuer', 'https://auth.example.test');
        config()->set('mcp_oauth.resource', 'https://auth.example.test/mcp');
        config()->set('mcp_server.api_base_url', 'https://api.example.test');
        /** @var AuthorizationTokenVerifierInterface&MockInterface $verifier */
        $verifier = Mockery::mock(AuthorizationTokenVerifierInterface::class);
        $verifier->shouldReceive('verify')->with('valid-token')->andReturn($this->verifiedToken());
        /** @var InternalApiTokenServiceInterface&MockInterface $internalApiTokenService */
        $internalApiTokenService = Mockery::mock(InternalApiTokenServiceInterface::class);
        if ($issueToken) {
            $internalApiTokenService->shouldReceive('issue')->once()->with('valid-token')->andReturn(new InternalApiToken('internal-secret', new DateTimeImmutable('+1 minute')));
        }
        $transport = new McpTransport($verifier, new AccountApiAdapter($internalApiTokenService));

        return $transport->handle(Request::create('/mcp', $method, server: $server + ['HTTP_AUTHORIZATION' => 'Bearer valid-token', 'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json, text/event-stream'], content: $raw ?? json_encode($message, JSON_THROW_ON_ERROR)));
    }

    private function verifiedToken(): ValidatedAuthorizationToken
    {
        $uuid = (string) Str::uuid7();
        $connection = new AuthorizationConnection(new AuthorizationConnectionIdentifier($uuid), new IdentityIdentifier($uuid), new OAuthClientIdentifier($uuid), [AuthorizationScope::WikiRead, AuthorizationScope::DraftRead], new AccountIdentifier($uuid), new AccountPrincipalIdentifier($uuid), 'https://auth.example.test/mcp', new DateTimeImmutable());

        return new ValidatedAuthorizationToken('source-id', $connection, [AuthorizationScope::WikiRead], new DateTimeImmutable('+1 hour'));
    }

    public function testInvalidTokenHasDiscoveryChallengeWithoutLeakingCredentials(): void
    {
        config()->set('mcp_server.enabled', true);
        config()->set('mcp_oauth.issuer', 'https://auth.example.test');
        config()->set('mcp_oauth.resource', 'https://auth.example.test/mcp');
        /** @var AuthorizationTokenVerifierInterface&MockInterface $verifier */
        $verifier = Mockery::mock(AuthorizationTokenVerifierInterface::class);
        $verifier->shouldReceive('verify')->once()->with('invalid-secret')->andThrow(new InvalidAuthorizationTokenException());
        /** @var InternalApiTokenServiceInterface&MockInterface $internalApiTokenService */
        $internalApiTokenService = Mockery::mock(InternalApiTokenServiceInterface::class);
        $transport = new McpTransport($verifier, new AccountApiAdapter($internalApiTokenService));
        $request = Request::create('/mcp', 'POST', server: ['HTTP_AUTHORIZATION' => 'Bearer invalid-secret', 'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json, text/event-stream'], content: '{"jsonrpc":"2.0","id":1,"method":"tools/list"}');
        $response = $transport->handle($request);
        self::assertSame(401, $response->getStatusCode());
        self::assertStringContainsString('resource_metadata="https://auth.example.test/.well-known/oauth-protected-resource/mcp"', (string) $response->headers->get('WWW-Authenticate'));
        self::assertStringNotContainsString('invalid-secret', (string) $response->getContent());
        self::assertFalse($response->headers->has('Set-Cookie'));
    }
}
