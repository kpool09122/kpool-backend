<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Repository;

use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Domain\Entity\PasskeyUser;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Identity\Infrastructure\Repository\PasskeyUserRepository;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\TestCase;

class PasskeyUserRepositoryTest extends TestCase
{
    public function testItIsBound(): void
    {
        $this->assertInstanceOf(
            PasskeyUserRepository::class,
            $this->app->make(PasskeyUserRepositoryInterface::class),
        );
    }

    #[Group('useDb')]
    public function testItPersistsAnUnlinkedUserAndLaterLinksItToAnIdentity(): void
    {
        $repository = $this->app->make(PasskeyUserRepositoryInterface::class);
        $identifier = new PasskeyUserIdentifier('123e4567-e89b-72d3-a456-426614174001');
        $repository->save(new PasskeyUser($identifier, null));

        $unlinked = $repository->findByIdentifier($identifier);
        $this->assertNotNull($unlinked);
        $this->assertNull($unlinked->identityIdentifier());

        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        CreateIdentity::create($identityIdentifier, ['email' => 'passkey-user@example.com']);
        $unlinked->linkToIdentity($identityIdentifier);
        $repository->save($unlinked);

        $linked = $repository->findByIdentityIdentifier($identityIdentifier);
        $this->assertNotNull($linked);
        $this->assertSame((string) $identifier, (string) $linked->identifier());
    }
}
