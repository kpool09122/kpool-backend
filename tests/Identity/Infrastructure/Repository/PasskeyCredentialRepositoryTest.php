<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Repository;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Entity\PasskeyUser;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Source\Identity\Infrastructure\Repository\PasskeyCredentialRepository;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\TestCase;

class PasskeyCredentialRepositoryTest extends TestCase
{
    public function testItIsBound(): void
    {
        $this->assertInstanceOf(
            PasskeyCredentialRepository::class,
            $this->app->make(PasskeyCredentialRepositoryInterface::class),
        );
    }

    #[Group('useDb')]
    public function testItPersistsAndRestoresAllCredentialTypesAndMutableAuthenticationState(): void
    {
        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        CreateIdentity::create($identityIdentifier, ['email' => 'passkey-repo@example.com']);
        $this->createPasskeyUser($identityIdentifier);
        $repository = $this->app->make(PasskeyCredentialRepositoryInterface::class);

        $credentials = [
            $this->credential('123e4567-e89b-72d3-a456-426614174001', 'c3luY2Vk', true, true, ['internal', 'hybrid']),
            $this->credential('123e4567-e89b-72d3-a456-426614174002', 'cGxhdGZvcm0', false, false, ['internal']),
            $this->credential('123e4567-e89b-72d3-a456-426614174003', 'c2VjdXJpdHkta2V5', false, false, ['usb', 'nfc']),
        ];

        foreach ($credentials as $credential) {
            $repository->save($credential);
        }

        $found = $repository->findByIdentityIdentifier($identityIdentifier);
        $this->assertCount(3, $found);
        $this->assertSame(['internal', 'hybrid'], $found[0]->transports());
        $this->assertTrue($found[0]->backupEligible());
        $this->assertTrue($found[0]->backupState());

        $usedAt = new DateTimeImmutable('2026-09-19T12:00:00+00:00');
        $found[0]->recordAuthentication(new CredentialSource('{"counter":7}'), 7, true, false, $usedAt);
        $found[0]->rename(new PasskeyDisplayName('Renamed passkey'));
        $repository->save($found[0]);
        $reloaded = $repository->findByCredentialId(new WebAuthnCredentialId('c3luY2Vk'));

        $this->assertNotNull($reloaded);
        $this->assertSame(7, $reloaded->signCount());
        $this->assertFalse($reloaded->backupState());
        $this->assertSame('Renamed passkey', (string) $reloaded->displayName());
        $this->assertSame($usedAt->format('Y-m-d H:i:s'), $reloaded->lastUsedAt()?->format('Y-m-d H:i:s'));
    }

    #[Group('useDb')]
    public function testCredentialIdHasAUniqueConstraint(): void
    {
        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        CreateIdentity::create($identityIdentifier, ['email' => 'passkey-unique@example.com']);
        $this->createPasskeyUser($identityIdentifier);
        $repository = $this->app->make(PasskeyCredentialRepositoryInterface::class);
        $repository->save($this->credential('123e4567-e89b-72d3-a456-426614174001', 'ZHVwbGljYXRl', false, false, ['usb']));

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        $repository->save($this->credential('123e4567-e89b-72d3-a456-426614174002', 'ZHVwbGljYXRl', false, false, ['nfc']));
    }

    #[Group('useDb')]
    public function testItDeletesACredentialByIdentifier(): void
    {
        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        CreateIdentity::create($identityIdentifier, ['email' => 'passkey-delete@example.com']);
        $this->createPasskeyUser($identityIdentifier);
        $repository = $this->app->make(PasskeyCredentialRepositoryInterface::class);
        $identifier = new PasskeyCredentialIdentifier('123e4567-e89b-72d3-a456-426614174001');
        $repository->save($this->credential((string) $identifier, 'ZGVsZXRlLW1l', false, false, ['internal']));

        $repository->delete($identifier);

        $this->assertNull($repository->findByIdentifier($identifier));
    }

    #[Group('useDb')]
    public function testItDeletesOnlyOtherCredentialsOwnedByTheSameIdentity(): void
    {
        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        $otherIdentityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174020');
        CreateIdentity::create($identityIdentifier, ['email' => 'passkey-recovery@example.com']);
        CreateIdentity::create($otherIdentityIdentifier, ['email' => 'other-passkey@example.com']);
        $this->createPasskeyUser($identityIdentifier);
        $this->createPasskeyUser($otherIdentityIdentifier, '123e4567-e89b-72d3-a456-426614174030');
        $repository = $this->app->make(PasskeyCredentialRepositoryInterface::class);
        $preserved = $this->credential('123e4567-e89b-72d3-a456-426614174001', 'cHJlc2VydmVk', false, false, ['internal']);
        $deleted = $this->credential('123e4567-e89b-72d3-a456-426614174002', 'ZGVsZXRlZA', false, false, ['usb']);
        $other = $this->credential(
            '123e4567-e89b-72d3-a456-426614174003',
            'b3RoZXI',
            false,
            false,
            ['nfc'],
            '123e4567-e89b-72d3-a456-426614174030',
        );
        $repository->save($preserved);
        $repository->save($deleted);
        $repository->save($other);

        $repository->deleteAllExcept($identityIdentifier, $preserved->identifier());

        $this->assertNotNull($repository->findByIdentifier($preserved->identifier()));
        $this->assertNull($repository->findByIdentifier($deleted->identifier()));
        $this->assertNotNull($repository->findByIdentifier($other->identifier()));
    }

    /** @param string[] $transports */
    private function credential(
        string $id,
        string $credentialId,
        bool $be,
        bool $bs,
        array $transports,
        string $passkeyUserIdentifier = '123e4567-e89b-72d3-a456-426614174010',
    ): PasskeyCredential {
        return new PasskeyCredential(
            new PasskeyCredentialIdentifier($id),
            new PasskeyUserIdentifier($passkeyUserIdentifier),
            new WebAuthnCredentialId($credentialId),
            new CredentialSource('{"credential":"source"}'),
            5,
            $be,
            $bs,
            $transports,
            new PasskeyDisplayName('Passkey'),
            null,
        );
    }

    private function createPasskeyUser(
        IdentityIdentifier $identityIdentifier,
        string $passkeyUserIdentifier = '123e4567-e89b-72d3-a456-426614174010',
    ): void {
        $user = new PasskeyUser(
            new PasskeyUserIdentifier($passkeyUserIdentifier),
            null,
        );
        $user->linkToIdentity($identityIdentifier);
        $this->app->make(PasskeyUserRepositoryInterface::class)->save($user);
    }
}
