<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Account\Account\Domain\Entity\AccountSnapshotChangeRequest;
use Source\Account\Account\Domain\Factory\AccountSnapshotChangeRequestFactoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeReason;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class AccountSnapshotChangeRequestFactory implements AccountSnapshotChangeRequestFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    public function createIdentifier(): AccountSnapshotChangeRequestIdentifier
    {
        return new AccountSnapshotChangeRequestIdentifier($this->uuidGenerator->generate());
    }

    public function create(AccountSnapshotChangeRequestIdentifier $requestIdentifier, AccountIdentifier $accountIdentifier, AccountSnapshotIdentifier $snapshotIdentifier, AccountSnapshotChangeReason $changeReason, DateTimeImmutable $requestedAt): AccountSnapshotChangeRequest
    {
        return new AccountSnapshotChangeRequest($requestIdentifier, $accountIdentifier, $snapshotIdentifier, $changeReason, $requestedAt, AccountSnapshotChangeRequestStatus::PENDING, null, null, null, null);
    }
}
