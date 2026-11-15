<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CreateAuthorizationRequest;

use Illuminate\Support\Str;
use Source\Identity\Application\UseCase\Command\CreateAuthorizationRequest\CreateAuthorizationRequestInput;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class CreateAuthorizationRequestInputTest extends TestCase
{
    public function testContract(): void
    {
        $clientIdentifier = new OAuthClientIdentifier((string) Str::uuid7());
        $codeChallenge = str_repeat('a', 43);
        $identity = new IdentityIdentifier((string) Str::uuid7());
        $input = new CreateAuthorizationRequestInput($identity, $clientIdentifier, 'https://client.test/cb', [AuthorizationScope::DraftWrite], 'https://mcp.test', $codeChallenge, 'state');

        $this->assertSame($identity, $input->identityIdentifier());
        $this->assertSame($clientIdentifier, $input->clientIdentifier());
        $this->assertSame('https://client.test/cb', $input->redirectUri());
        $this->assertSame([AuthorizationScope::DraftWrite], $input->scopes());
        $this->assertSame('https://mcp.test', $input->resource());
        $this->assertSame($codeChallenge, $input->codeChallenge());
        $this->assertSame('state', $input->state());
    }
}
