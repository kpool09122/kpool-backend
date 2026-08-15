<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Affiliation\Infrastructure\Repository\AffiliationRepository;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AffiliationRepositoryTest extends TestCase
{
    #[Group('useDb')]
    public function testFindActiveByTalentAccountReturnsOnlyActiveAffiliation(): void
    {
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $activeAgencyIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $terminatedAgencyIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $pendingAgencyIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $activeAffiliationIdentifier = StrTestHelper::generateUuid();

        $this->insertAffiliation(
            $activeAffiliationIdentifier,
            $activeAgencyIdentifier,
            $talentAccountIdentifier,
            AffiliationStatus::ACTIVE,
            activatedAt: new DateTimeImmutable('2026-01-02 00:00:00'),
        );
        $this->insertAffiliation(
            StrTestHelper::generateUuid(),
            $terminatedAgencyIdentifier,
            $talentAccountIdentifier,
            AffiliationStatus::TERMINATED,
            activatedAt: new DateTimeImmutable('2026-01-03 00:00:00'),
            terminatedAt: new DateTimeImmutable('2026-01-04 00:00:00'),
        );
        $this->insertAffiliation(
            StrTestHelper::generateUuid(),
            $pendingAgencyIdentifier,
            $talentAccountIdentifier,
            AffiliationStatus::PENDING,
        );

        $affiliation = (new AffiliationRepository())->findActiveByTalentAccount($talentAccountIdentifier);

        $this->assertNotNull($affiliation);
        $this->assertSame($activeAffiliationIdentifier, (string) $affiliation->affiliationIdentifier());
        $this->assertSame((string) $activeAgencyIdentifier, (string) $affiliation->agencyAccountIdentifier());
        $this->assertSame((string) $talentAccountIdentifier, (string) $affiliation->talentAccountIdentifier());
        $this->assertSame(AffiliationStatus::ACTIVE, $affiliation->status());
    }

    #[Group('useDb')]
    public function testFindActiveByTalentAccountReturnsNullWhenOnlyTerminatedOrPendingExists(): void
    {
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $this->insertAffiliation(
            StrTestHelper::generateUuid(),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            $talentAccountIdentifier,
            AffiliationStatus::TERMINATED,
            activatedAt: new DateTimeImmutable('2026-01-03 00:00:00'),
            terminatedAt: new DateTimeImmutable('2026-01-04 00:00:00'),
        );
        $this->insertAffiliation(
            StrTestHelper::generateUuid(),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            $talentAccountIdentifier,
            AffiliationStatus::PENDING,
        );

        $this->assertNull((new AffiliationRepository())->findActiveByTalentAccount($talentAccountIdentifier));
    }

    #[Group('useDb')]
    public function testExistsOpenAffiliationReturnsTrueForPendingOrActiveSamePair(): void
    {
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $repository = new AffiliationRepository();

        $this->assertFalse($repository->existsOpenAffiliation($agencyAccountIdentifier, $talentAccountIdentifier));

        $this->insertAffiliation(
            StrTestHelper::generateUuid(),
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            AffiliationStatus::PENDING,
        );

        $this->assertTrue($repository->existsOpenAffiliation($agencyAccountIdentifier, $talentAccountIdentifier));
    }

    #[Group('useDb')]
    public function testExistsOpenAffiliationReturnsFalseForTerminatedSamePair(): void
    {
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $this->insertAffiliation(
            StrTestHelper::generateUuid(),
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            AffiliationStatus::TERMINATED,
            activatedAt: new DateTimeImmutable('2026-01-03 00:00:00'),
            terminatedAt: new DateTimeImmutable('2026-01-04 00:00:00'),
        );

        $this->assertFalse((new AffiliationRepository())->existsOpenAffiliation($agencyAccountIdentifier, $talentAccountIdentifier));
    }

    private function insertAffiliation(
        string $identifier,
        AccountIdentifier $agencyAccountIdentifier,
        AccountIdentifier $talentAccountIdentifier,
        AffiliationStatus $status,
        ?DateTimeImmutable $activatedAt = null,
        ?DateTimeImmutable $terminatedAt = null,
    ): void {
        DB::table('account_affiliations')->insert([
            'id' => $identifier,
            'agency_account_id' => (string) $agencyAccountIdentifier,
            'talent_account_id' => (string) $talentAccountIdentifier,
            'requested_by' => (string) $agencyAccountIdentifier,
            'status' => $status->value,
            'revenue_share_percentage' => null,
            'contract_notes' => null,
            'requested_at' => '2026-01-01 00:00:00',
            'activated_at' => $activatedAt?->format('Y-m-d H:i:s'),
            'terminated_at' => $terminatedAt?->format('Y-m-d H:i:s'),
        ]);
    }
}
