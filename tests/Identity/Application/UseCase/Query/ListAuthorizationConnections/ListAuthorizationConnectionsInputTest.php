<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Query\ListAuthorizationConnections;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Query\ListAuthorizationConnections\ListAuthorizationConnectionsInput;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class ListAuthorizationConnectionsInputTest extends TestCase
{
    public function testPreservesValueObjectInstances(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $input = new ListAuthorizationConnectionsInput($identity);
        self::assertSame($identity, $input->identityIdentifier());
    }
}
