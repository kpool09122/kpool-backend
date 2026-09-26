<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Query;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Application\UseCase\Query\ListPasskeys\ListPasskeysInput;
use Source\Identity\Application\UseCase\Query\ListPasskeys\ListPasskeysInterface;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\TestCase;

class ListPasskeysTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsOnlyTheAuthenticatedIdentityPasskeysWithoutSecrets(): void
    {
        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        $otherIdentityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174100');
        CreateIdentity::create($identityIdentifier, ['email' => 'owner@example.com']);
        CreateIdentity::create($otherIdentityIdentifier, ['email' => 'other@example.com']);
        $this->authorizePasskeyManagement($identityIdentifier);
        $this->insertPasskeyUser('123e4567-e89b-72d3-a456-426614174010', $identityIdentifier);
        $this->insertPasskeyUser('123e4567-e89b-72d3-a456-426614174110', $otherIdentityIdentifier);
        $this->insertCredential(
            id: '123e4567-e89b-72d3-a456-426614174001',
            userId: '123e4567-e89b-72d3-a456-426614174010',
            displayName: 'MacBook Touch ID',
            transports: ['internal', 'hybrid'],
            backupEligible: true,
            backupState: false,
            lastUsedAt: '2026-09-23 01:02:03',
            createdAt: '2026-09-20 04:05:06',
        );
        $this->insertCredential(
            id: '123e4567-e89b-72d3-a456-426614174002',
            userId: '123e4567-e89b-72d3-a456-426614174010',
            displayName: 'Security key',
            transports: ['usb'],
            backupEligible: false,
            backupState: false,
            lastUsedAt: null,
            createdAt: '2026-09-21 04:05:06',
        );
        $this->insertCredential(
            id: '123e4567-e89b-72d3-a456-426614174101',
            userId: '123e4567-e89b-72d3-a456-426614174110',
            displayName: 'Other key',
            transports: ['usb'],
            backupEligible: false,
            backupState: false,
            lastUsedAt: null,
            createdAt: '2026-09-19 04:05:06',
        );

        $passkeys = $this->app->make(ListPasskeysInterface::class)
            ->process(new ListPasskeysInput($identityIdentifier));

        $this->assertCount(2, $passkeys);
        $payload = $passkeys[0]->toArray();
        $this->assertSame('123e4567-e89b-72d3-a456-426614174001', $payload['passkeyIdentifier']);
        $this->assertSame('MacBook Touch ID', $payload['displayName']);
        $this->assertSame(['internal', 'hybrid'], $payload['transports']);
        $this->assertTrue($payload['backupEligible']);
        $this->assertFalse($payload['backupState']);
        $this->assertSame('2026-09-23T01:02:03+00:00', $payload['lastUsedAt']);
        $this->assertSame('2026-09-20T04:05:06+00:00', $payload['createdAt']);
        $this->assertArrayNotHasKey('credentialId', $payload);
        $this->assertArrayNotHasKey('credentialSource', $payload);
        $this->assertArrayNotHasKey('publicKey', $payload);
        $this->assertArrayNotHasKey('passkeyUserIdentifier', $payload);
        $this->assertSame('123e4567-e89b-72d3-a456-426614174002', $passkeys[1]->passkeyIdentifier());
        $this->assertNull($passkeys[1]->lastUsedAt());
    }

    #[Group('useDb')]
    public function testProcessReturnsEmptyListWhenPasskeyUserDoesNotExist(): void
    {
        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        CreateIdentity::create($identityIdentifier, ['email' => 'without-passkey@example.com']);
        $this->authorizePasskeyManagement($identityIdentifier);

        $passkeys = $this->app->make(ListPasskeysInterface::class)
            ->process(new ListPasskeysInput($identityIdentifier));

        $this->assertSame([], $passkeys);
    }

    public function testProcessRejectsListingWithoutStepUpAuthorization(): void
    {
        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        /** @var MockInterface&StepUpAuthenticationStorageServiceInterface $stepUp */
        $stepUp = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $stepUp->shouldReceive('requireValid')->once()->with(
            Mockery::on(static fn (IdentityIdentifier $identifier): bool => (string) $identifier === (string) $identityIdentifier),
            StepUpAuthenticationScope::PASSKEY_MANAGE,
        )->andThrow(new StepUpAuthenticationRequiredException());
        $this->app->instance(StepUpAuthenticationStorageServiceInterface::class, $stepUp);

        $this->expectException(StepUpAuthenticationRequiredException::class);
        $this->app->make(ListPasskeysInterface::class)->process(new ListPasskeysInput($identityIdentifier));
    }

    private function authorizePasskeyManagement(IdentityIdentifier $identityIdentifier): void
    {
        /** @var MockInterface&StepUpAuthenticationStorageServiceInterface $stepUp */
        $stepUp = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $stepUp->shouldReceive('requireValid')->once()->with(
            Mockery::on(static fn (IdentityIdentifier $identifier): bool => (string) $identifier === (string) $identityIdentifier),
            StepUpAuthenticationScope::PASSKEY_MANAGE,
        )->andReturn(new StepUpAuthentication(
            $identityIdentifier,
            StepUpAuthenticationMethod::PASSKEY,
            new DateTimeImmutable(),
            StepUpAuthenticationScope::PASSKEY_MANAGE,
            new DateTimeImmutable('+10 minutes'),
        ));
        $this->app->instance(StepUpAuthenticationStorageServiceInterface::class, $stepUp);
    }

    private function insertPasskeyUser(string $id, IdentityIdentifier $identityIdentifier): void
    {
        DB::table('passkey_users')->insert([
            'id' => $id,
            'identity_id' => (string) $identityIdentifier,
            'created_at' => '2026-09-19 00:00:00',
        ]);
    }

    /** @param string[] $transports */
    private function insertCredential(
        string $id,
        string $userId,
        string $displayName,
        array $transports,
        bool $backupEligible,
        bool $backupState,
        ?string $lastUsedAt,
        string $createdAt,
    ): void {
        DB::table('passkey_credentials')->insert([
            'id' => $id,
            'passkey_user_id' => $userId,
            'credential_id' => 'credential-'.$id,
            'credential_source' => json_encode(['publicKeyCredentialSource' => 'secret'], JSON_THROW_ON_ERROR),
            'sign_count' => 1,
            'backup_eligible' => $backupEligible,
            'backup_state' => $backupState,
            'transports' => json_encode($transports, JSON_THROW_ON_ERROR),
            'display_name' => $displayName,
            'last_used_at' => $lastUsedAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
