<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Account\Account\Domain\Entity\AccountSnapshot;
use Source\Account\Account\Domain\Factory\AccountSnapshotFactoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotProfile;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotStatus;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class AccountSnapshotFactory implements AccountSnapshotFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    public function createIdentifier(): AccountSnapshotIdentifier
    {
        return new AccountSnapshotIdentifier($this->uuidGenerator->generate());
    }

    public function create(AccountSnapshotIdentifier $snapshotIdentifier, AccountIdentifier $accountIdentifier, AccountSnapshotStatus $status, AccountSnapshotProfile $account, array $documents): AccountSnapshot
    {
        return new AccountSnapshot($snapshotIdentifier, $accountIdentifier, $status, $account, $documents, new DateTimeImmutable());
    }
}
