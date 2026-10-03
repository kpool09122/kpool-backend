<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\Entity\PasskeyUser;
use Source\Identity\Domain\Exception\PasskeyUserAlreadyLinkedException;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class PasskeyUserTest extends TestCase
{
    public function testItCanExistBeforeAnIdentityIsCreated(): void
    {
        $identifier = new PasskeyUserIdentifier('123e4567-e89b-72d3-a456-426614174001');
        $user = new PasskeyUser($identifier, null);

        $this->assertSame($identifier, $user->identifier());
        $this->assertNull($user->identityIdentifier());
    }

    public function testItCanBeLinkedToAnIdentityExactlyOnce(): void
    {
        $user = new PasskeyUser(
            new PasskeyUserIdentifier('123e4567-e89b-72d3-a456-426614174001'),
            null,
        );
        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');

        $user->linkToIdentity($identityIdentifier);

        $this->assertSame($identityIdentifier, $user->identityIdentifier());

        $this->expectException(PasskeyUserAlreadyLinkedException::class);
        $user->linkToIdentity(new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174002'));
    }
}
