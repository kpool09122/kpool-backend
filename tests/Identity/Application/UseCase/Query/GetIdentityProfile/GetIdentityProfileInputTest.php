<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Query\GetIdentityProfile;

use Source\Identity\Application\UseCase\Query\GetIdentityProfile\GetIdentityProfileInput;
use Source\Identity\Application\UseCase\Query\GetIdentityProfile\GetIdentityProfileInputPort;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class GetIdentityProfileInputTest extends TestCase
{
    public function test__construct(): void
    {
        $identityIdentifier = new IdentityIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14d5fe');

        $input = new GetIdentityProfileInput($identityIdentifier);

        $this->assertInstanceOf(GetIdentityProfileInputPort::class, $input);
        $this->assertSame($identityIdentifier, $input->identityIdentifier());
    }
}
