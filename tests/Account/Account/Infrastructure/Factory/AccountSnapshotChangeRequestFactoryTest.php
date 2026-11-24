<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Infrastructure\Factory\AccountSnapshotChangeRequestFactory;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Tests\Helper\CreateAccountSnapshotChangeRequest;

class AccountSnapshotChangeRequestFactoryTest extends TestCase
{
    public function testCreatesPendingRequestPreservingAllProvidedValues(): void
    {
        $entity = CreateAccountSnapshotChangeRequest::entity();
        $uuid = $this->createMock(UuidGeneratorInterface::class);
        $uuid->expects(self::once())->method('generate')->willReturn((string) $entity->requestIdentifier());
        $factory = new AccountSnapshotChangeRequestFactory($uuid);
        $identifier = $factory->createIdentifier();
        self::assertEquals($entity->requestIdentifier(), $identifier);
        $request = $factory->create($identifier, $entity->accountIdentifier(), $entity->snapshotIdentifier(), $entity->changeReason(), $entity->requestedAt());
        foreach (['accountIdentifier','snapshotIdentifier','changeReason','requestedAt'] as $getter) {
            self::assertSame($entity->$getter(), $request->$getter());
        }
        self::assertSame('pending', $request->status()->value);
        self::assertNull($request->reviewedSnapshotIdentifier());
        self::assertNull($request->reviewedBy());
        self::assertNull($request->reviewedAt());
        self::assertNull($request->rejectionReason());
    }
}
