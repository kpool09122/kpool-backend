<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CreateAuthorizationRequest;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\PendingAuthorization;
use Source\Identity\Application\UseCase\Command\CreateAuthorizationRequest\CreateAuthorizationRequestOutput;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class CreateAuthorizationRequestOutputTest extends TestCase
{
    public function testContract(): void
    {
        $identity = new IdentityIdentifier((string) Str::uuid7());
        $account = new AccountIdentifier((string) Str::uuid7());
        $principal = new PrincipalIdentifier((string) Str::uuid7());
        $parameters = new AuthorizationCodeParameters(new OAuthClientIdentifier((string) Str::uuid7()), 'https://client.test/cb', [AuthorizationScope::DraftWrite], 'https://mcp.test', str_repeat('a', 43), 'state');
        $pending = new PendingAuthorization('request', $parameters, new DateTimeImmutable('2030-01-01T00:00:00+00:00'), $identity);
        $output = new CreateAuthorizationRequestOutput();
        $this->assertSame([], $output->toArray());
        $output->setPending($pending);
        $this->assertSame(['requestIdentifier' => 'request', 'expiresAt' => '2030-01-01T00:00:00+00:00'], $output->toArray());
    }
}
