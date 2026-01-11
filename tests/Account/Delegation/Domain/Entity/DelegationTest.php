<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\TestCase;
use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class DelegationTest extends TestCase
{
    public function test__construct(): void
    {
        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $delegateIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegatorIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $status = DelegationStatus::PENDING;
        $direction = DelegationDirection::FROM_AGENCY;
        $requestedAt = new DateTimeImmutable();

        $delegation = new Delegation(
            $delegationIdentifier,
            $affiliationIdentifier,
            $delegateIdentifier,
            $delegatorIdentifier,
            $status,
            $direction,
            $requestedAt,
            null,
            null,
        );

        $this->assertSame($delegationIdentifier, $delegation->delegationIdentifier());
        $this->assertSame($affiliationIdentifier, $delegation->affiliationIdentifier());
        $this->assertSame($delegateIdentifier, $delegation->delegateIdentifier());
        $this->assertSame($delegatorIdentifier, $delegation->delegatorIdentifier());
        $this->assertSame($status, $delegation->status());
        $this->assertSame($direction, $delegation->direction());
        $this->assertSame($requestedAt, $delegation->requestedAt());
        $this->assertNull($delegation->approvedAt());
        $this->assertNull($delegation->revokedAt());
    }

    public function testDirectionFromTalent(): void
    {
        $delegation = new Delegation(
            new DelegationIdentifier(StrTestHelper::generateUuid()),
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            DelegationStatus::PENDING,
            DelegationDirection::FROM_TALENT,
            new DateTimeImmutable(),
            null,
            null,
        );

        $this->assertSame(DelegationDirection::FROM_TALENT, $delegation->direction());
    }

    public function testApprove(): void
    {
        $delegation = $this->createPendingDelegation();

        $delegation->approve();

        $this->assertTrue($delegation->isApproved());
        $this->assertNotNull($delegation->approvedAt());
    }

    public function testApproveThrowsWhenNotPending(): void
    {
        $delegation = $this->createApprovedDelegation();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Only pending delegations can be approved.');

        $delegation->approve();
    }

    public function testRevoke(): void
    {
        $delegation = $this->createApprovedDelegation();

        $delegation->revoke();

        $this->assertTrue($delegation->isRevoked());
        $this->assertNotNull($delegation->revokedAt());
    }

    public function testRevokeThrowsWhenNotApproved(): void
    {
        $delegation = $this->createPendingDelegation();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Only approved delegations can be revoked.');

        $delegation->revoke();
    }

    public function testIsPending(): void
    {
        $delegation = $this->createPendingDelegation();
        $this->assertTrue($delegation->isPending());
        $this->assertFalse($delegation->isApproved());
        $this->assertFalse($delegation->isRevoked());
    }

    public function testIsApproved(): void
    {
        $delegation = $this->createApprovedDelegation();
        $this->assertFalse($delegation->isPending());
        $this->assertTrue($delegation->isApproved());
        $this->assertFalse($delegation->isRevoked());
    }

    public function testIsRevoked(): void
    {
        $delegation = $this->createRevokedDelegation();
        $this->assertFalse($delegation->isPending());
        $this->assertFalse($delegation->isApproved());
        $this->assertTrue($delegation->isRevoked());
    }

    private function createPendingDelegation(): Delegation
    {
        return new Delegation(
            new DelegationIdentifier(StrTestHelper::generateUuid()),
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            DelegationStatus::PENDING,
            DelegationDirection::FROM_AGENCY,
            new DateTimeImmutable(),
            null,
            null,
        );
    }

    private function createApprovedDelegation(): Delegation
    {
        return new Delegation(
            new DelegationIdentifier(StrTestHelper::generateUuid()),
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            DelegationStatus::APPROVED,
            DelegationDirection::FROM_AGENCY,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            null,
        );
    }

    private function createRevokedDelegation(): Delegation
    {
        return new Delegation(
            new DelegationIdentifier(StrTestHelper::generateUuid()),
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            DelegationStatus::REVOKED,
            DelegationDirection::FROM_AGENCY,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );
    }
}
