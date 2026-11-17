<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\RevokeAuthorization;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\RevokeAuthorization\RevokeAuthorizationInput;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class RevokeAuthorizationInputTest extends TestCase
{
    public function testPreservesValueObjectInstances(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $connection = new AuthorizationConnectionIdentifier(StrTestHelper::generateUuid());
        $input = new RevokeAuthorizationInput($identity, $connection);
        self::assertSame($connection, $input->connectionIdentifier());
        self::assertSame($identity, $input->identityIdentifier());
    }
}
