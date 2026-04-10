<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Application\UseCase\Command\ApproveDelegation;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation\ApproveDelegationOutput;
use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class ApproveDelegationOutputTest extends TestCase
{
    public function testToArrayWithDelegation(): void
    {
        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $delegateIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegatorIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $requestedAt = new DateTimeImmutable('-1 day');
        $approvedAt = new DateTimeImmutable();

        $delegation = new Delegation(
            $delegationIdentifier,
            $affiliationIdentifier,
            $delegateIdentifier,
            $delegatorIdentifier,
            DelegationStatus::APPROVED,
            DelegationDirection::FROM_AGENCY,
            $requestedAt,
            $approvedAt,
            null,
        );

        $output = new ApproveDelegationOutput();
        $output->setDelegation($delegation);

        $result = $output->toArray();

        $this->assertSame((string) $delegationIdentifier, $result['delegationIdentifier']);
        $this->assertSame((string) $affiliationIdentifier, $result['affiliationIdentifier']);
        $this->assertSame((string) $delegateIdentifier, $result['delegateIdentifier']);
        $this->assertSame((string) $delegatorIdentifier, $result['delegatorIdentifier']);
        $this->assertSame(DelegationStatus::APPROVED->value, $result['status']);
        $this->assertSame(DelegationDirection::FROM_AGENCY->value, $result['direction']);
        $this->assertSame($requestedAt->format(DateTimeInterface::ATOM), $result['requestedAt']);
        $this->assertSame($approvedAt->format(DateTimeInterface::ATOM), $result['approvedAt']);
        $this->assertNull($result['revokedAt']);
    }

    public function testToArrayWithoutDelegation(): void
    {
        $output = new ApproveDelegationOutput();
        $this->assertSame([], $output->toArray());
    }
}
