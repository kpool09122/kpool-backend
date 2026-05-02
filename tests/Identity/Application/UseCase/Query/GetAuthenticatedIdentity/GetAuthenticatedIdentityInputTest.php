<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Query\GetAuthenticatedIdentity;

use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInput;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class GetAuthenticatedIdentityInputTest extends TestCase
{
    public function test__construct(): void
    {
        $identityIdentifier = new IdentityIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14d5fe');
        $input = new GetAuthenticatedIdentityInput($identityIdentifier);

        $this->assertSame((string) $identityIdentifier, (string) $input->identityIdentifier());
    }
}
