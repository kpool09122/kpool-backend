<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Repository;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Infrastructure\Repository\PasskeyCredentialRepository;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PasskeyCredentialRepositoryTest extends TestCase
{
    public function testRepositoryBinding(): void
    {
        $this->assertInstanceOf(
            PasskeyCredentialRepository::class,
            $this->app->make(PasskeyCredentialRepositoryInterface::class),
        );
    }

    #[Group('useDb')]
    public function testMultipleCredentialsCanBeSavedLoadedUpdatedAndDeleted(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier, ['email' => 'passkeys@example.com']);
        $first = $this->credential($identityIdentifier, 'credential-one', 'Phone');
        $second = $this->credential($identityIdentifier, "credential-\x00-two", 'Security key', true, true);
        $repository = $this->repository();

        $repository->save($first);
        $repository->save($second);

        $this->assertSame($first->identifier(), $repository->findByCredentialId('credential-one')?->identifier());
        $this->assertSame($second->identifier(), $repository->findByCredentialId("credential-\x00-two")?->identifier());
        $this->assertCount(2, $repository->findByIdentity($identityIdentifier));

        $usedAt = new DateTimeImmutable();
        $second->rename('Renamed key');
        $second->recordAuthentication('{"updated":true}', 0, true, false, $usedAt);
        $repository->save($second);
        $updated = $repository->findByIdentifier($second->identifier());

        $this->assertNotNull($updated);
        $this->assertSame('Renamed key', $updated->displayName());
        $this->assertFalse($updated->backupState());
        $this->assertSame($usedAt->format('Y-m-d H:i:s'), $updated->lastUsedAt()?->format('Y-m-d H:i:s'));

        $repository->delete($first);
        $this->assertNull($repository->findByIdentifier($first->identifier()));
        $this->assertCount(1, $repository->findByIdentity($identityIdentifier));
    }

    private function repository(): PasskeyCredentialRepositoryInterface
    {
        return $this->app->make(PasskeyCredentialRepositoryInterface::class);
    }

    private function credential(
        IdentityIdentifier $identityIdentifier,
        string $credentialId,
        string $displayName,
        bool $backupEligible = false,
        bool $backupState = false,
    ): PasskeyCredential {
        return new PasskeyCredential(
            StrTestHelper::generateUuid(),
            $identityIdentifier,
            $credentialId,
            '{"credential":"source"}',
            0,
            $backupEligible,
            $backupState,
            ['internal', 'usb'],
            $displayName,
        );
    }
}
