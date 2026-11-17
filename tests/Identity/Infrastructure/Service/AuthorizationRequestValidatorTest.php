<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Models\Identity\McpClient;
use Laravel\Passport\ClientRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Application\Exception\InvalidAuthorizationRequestException;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Identity\Infrastructure\Service\AuthorizationRequestValidator;
use Tests\Helper\CreateAuthorizationConnection;
use Tests\TestCase;

#[Group('useDb')]
class AuthorizationRequestValidatorTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function invalidRequests(): array
    {
        return ['foreign redirect' => ['redirect'], 'loopback port mismatch' => ['port'], 'invalid resource' => ['resource'], 'empty scopes' => ['scopes'], 'duplicate scopes' => ['duplicates'], 'bad challenge' => ['challenge'], 'oversized state' => ['state'], 'unknown client' => ['client']];
    }

    #[DataProvider('invalidRequests')]
    public function testRejectsInvalidAuthorizationRequest(string $case): void
    {
        config()->set('mcp_oauth.resource', CreateAuthorizationConnection::RESOURCE);
        config()->set('mcp_oauth.issuer', 'https://auth.example.test');
        $redirect = $case === 'port' ? 'http://127.0.0.1:1234/cb' : CreateAuthorizationConnection::REDIRECT;
        $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient('Client', [$redirect], false);
        $parameters = new AuthorizationCodeParameters(
            new OAuthClientIdentifier($case === 'client' ? '0199f700-0000-7000-8000-000000000099' : $client->id),
            match ($case) {
                'redirect' => 'https://evil.test/cb', 'port' => 'http://127.0.0.1:5678/cb', default => $redirect
            },
            match ($case) {
                'scopes' => [], 'duplicates' => [AuthorizationScope::WikiRead, AuthorizationScope::WikiRead], default => [AuthorizationScope::WikiRead]
            },
            $case === 'resource' ? 'https://evil.test/mcp' : CreateAuthorizationConnection::RESOURCE,
            $case === 'challenge' ? 'invalid' : str_repeat('a', 43),
            $case === 'state' ? str_repeat('a', 2049) : 'state',
        );
        $this->expectException(InvalidAuthorizationRequestException::class);
        app(AuthorizationRequestValidator::class)->validate($parameters);
    }

    public function testValidatesRegisteredClientAndExactRequest(): void
    {
        config()->set('mcp_oauth.resource', CreateAuthorizationConnection::RESOURCE);
        config()->set('mcp_oauth.issuer', 'https://auth.example.test');
        $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient('ChatGPT', [CreateAuthorizationConnection::REDIRECT], false);
        $parameters = new AuthorizationCodeParameters(new OAuthClientIdentifier($client->id), CreateAuthorizationConnection::REDIRECT, [AuthorizationScope::DraftWrite], CreateAuthorizationConnection::RESOURCE, str_repeat('a', 43), 'state');
        $validator = app(AuthorizationRequestValidator::class);
        $this->assertSame('ChatGPT', $validator->validate($parameters));
        McpClient::query()->whereKey($client->id)->update(['revoked' => true]);
        $this->expectException(InvalidAuthorizationRequestException::class);
        $validator->validate($parameters);
    }
}
