<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Query\ListPasskeys;

use Source\Identity\Application\UseCase\Query\ListPasskeys\ListPasskeysInput;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class ListPasskeysInputTest extends TestCase
{
    public function testItExposesIdentityIdentifier(): void
    {
        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');

        $this->assertSame($identityIdentifier, (new ListPasskeysInput($identityIdentifier))->identityIdentifier());
    }
}
