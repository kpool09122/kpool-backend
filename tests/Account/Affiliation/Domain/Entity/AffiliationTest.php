<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\TestCase;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class AffiliationTest extends TestCase
{
    public function test__construct(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $requestedBy = $agencyAccountIdentifier;
        $status = AffiliationStatus::PENDING;
        $terms = new AffiliationTerms(new Percentage(30), 'Contract notes');
        $requestedAt = new DateTimeImmutable();

        $affiliation = new Affiliation(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $requestedBy,
            $status,
            $terms,
            $requestedAt,
            null,
            null,
        );

        $this->assertSame($affiliationIdentifier, $affiliation->affiliationIdentifier());
        $this->assertSame($agencyAccountIdentifier, $affiliation->agencyAccountIdentifier());
        $this->assertSame($talentAccountIdentifier, $affiliation->talentAccountIdentifier());
        $this->assertSame($requestedBy, $affiliation->requestedBy());
        $this->assertSame($status, $affiliation->status());
        $this->assertSame($terms, $affiliation->terms());
        $this->assertSame($requestedAt, $affiliation->requestedAt());
        $this->assertNull($affiliation->activatedAt());
        $this->assertNull($affiliation->terminatedAt());
    }

    public function testApprove(): void
    {
        $affiliation = $this->createPendingAffiliation();

        $affiliation->approve();

        $this->assertTrue($affiliation->isActive());
        $this->assertNotNull($affiliation->activatedAt());
    }

    public function testApproveThrowsWhenNotPending(): void
    {
        $affiliation = $this->createActiveAffiliation();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Only pending affiliations can be approved.');

        $affiliation->approve();
    }

    public function testTerminate(): void
    {
        $affiliation = $this->createActiveAffiliation();

        $affiliation->terminate();

        $this->assertTrue($affiliation->isTerminated());
        $this->assertNotNull($affiliation->terminatedAt());
    }

    public function testTerminateThrowsWhenNotActive(): void
    {
        $affiliation = $this->createPendingAffiliation();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Only active affiliations can be terminated.');

        $affiliation->terminate();
    }

    public function testUpdateTerms(): void
    {
        $affiliation = $this->createActiveAffiliation();
        $newTerms = new AffiliationTerms(new Percentage(50), 'Updated notes');

        $affiliation->updateTerms($newTerms);

        $this->assertSame($newTerms, $affiliation->terms());
    }

    public function testUpdateTermsThrowsWhenNotActive(): void
    {
        $affiliation = $this->createPendingAffiliation();
        $newTerms = new AffiliationTerms(new Percentage(50), 'Updated notes');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Terms can only be updated for active affiliations.');

        $affiliation->updateTerms($newTerms);
    }

    public function testIsPending(): void
    {
        $affiliation = $this->createPendingAffiliation();
        $this->assertTrue($affiliation->isPending());
        $this->assertFalse($affiliation->isActive());
        $this->assertFalse($affiliation->isTerminated());
    }

    public function testIsActive(): void
    {
        $affiliation = $this->createActiveAffiliation();
        $this->assertFalse($affiliation->isPending());
        $this->assertTrue($affiliation->isActive());
        $this->assertFalse($affiliation->isTerminated());
    }

    public function testIsTerminated(): void
    {
        $affiliation = $this->createTerminatedAffiliation();
        $this->assertFalse($affiliation->isPending());
        $this->assertFalse($affiliation->isActive());
        $this->assertTrue($affiliation->isTerminated());
    }

    public function testIsRequestedByAgency(): void
    {
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $affiliation = new Affiliation(
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $agencyAccountIdentifier,
            AffiliationStatus::PENDING,
            null,
            new DateTimeImmutable(),
            null,
            null,
        );

        $this->assertTrue($affiliation->isRequestedByAgency());
        $this->assertFalse($affiliation->isRequestedByTalent());
    }

    public function testIsRequestedByTalent(): void
    {
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $affiliation = new Affiliation(
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $talentAccountIdentifier,
            AffiliationStatus::PENDING,
            null,
            new DateTimeImmutable(),
            null,
            null,
        );

        $this->assertFalse($affiliation->isRequestedByAgency());
        $this->assertTrue($affiliation->isRequestedByTalent());
    }

    public function testApproverAccountIdentifierWhenRequestedByAgency(): void
    {
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $affiliation = new Affiliation(
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $agencyAccountIdentifier,
            AffiliationStatus::PENDING,
            null,
            new DateTimeImmutable(),
            null,
            null,
        );

        $this->assertSame(
            (string) $talentAccountIdentifier,
            (string) $affiliation->approverAccountIdentifier()
        );
    }

    public function testApproverAccountIdentifierWhenRequestedByTalent(): void
    {
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $affiliation = new Affiliation(
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $talentAccountIdentifier,
            AffiliationStatus::PENDING,
            null,
            new DateTimeImmutable(),
            null,
            null,
        );

        $this->assertSame(
            (string) $agencyAccountIdentifier,
            (string) $affiliation->approverAccountIdentifier()
        );
    }

    private function createPendingAffiliation(): Affiliation
    {
        return new Affiliation(
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            AffiliationStatus::PENDING,
            null,
            new DateTimeImmutable(),
            null,
            null,
        );
    }

    private function createActiveAffiliation(): Affiliation
    {
        return new Affiliation(
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            AffiliationStatus::ACTIVE,
            null,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            null,
        );
    }

    private function createTerminatedAffiliation(): Affiliation
    {
        return new Affiliation(
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            AffiliationStatus::TERMINATED,
            null,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );
    }
}
