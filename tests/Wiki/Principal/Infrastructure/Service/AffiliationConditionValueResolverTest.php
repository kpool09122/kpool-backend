<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Service;

use DateTimeImmutable;
use Mockery;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\ValueObject\ConditionValue;
use Source\Wiki\Principal\Infrastructure\Service\AffiliationConditionValueResolver;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AffiliationConditionValueResolverTest extends TestCase
{
    public function testResolveAffiliatedTalentIdsReturnsEmptyArrayWhenTalentWikiDoesNotExist(): void
    {
        $agencyAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $affiliation = $this->activeAffiliation($agencyAccountId, $talentAccountId);
        $principal = $this->principal((string) $agencyAccountId);

        /** @var AffiliationRepositoryInterface&Mockery\MockInterface $affiliationRepository */
        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findByAgencyAccount')
            ->once()
            ->with(Mockery::on(fn (AccountIdentifier $id) => (string) $id === (string) $agencyAccountId), AffiliationStatus::ACTIVE)
            ->andReturn([$affiliation]);

        /** @var WikiRepositoryInterface&Mockery\MockInterface $wikiRepository */
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByOwnerAccountId')
            ->once()
            ->with($talentAccountId, ResourceType::TALENT)
            ->andReturnNull();

        $resolver = new AffiliationConditionValueResolver($affiliationRepository, $wikiRepository);

        $this->assertSame([], $resolver->resolve(ConditionValue::PRINCIPAL_AFFILIATED_TALENT_IDS, $principal));
    }

    public function testResolveAffiliatedTalentIdsReturnsTalentWikiIdsAtEvaluationTime(): void
    {
        $agencyAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentWikiId = new WikiIdentifier(StrTestHelper::generateUuid());
        $affiliation = $this->activeAffiliation($agencyAccountId, $talentAccountId);
        $principal = $this->principal((string) $agencyAccountId);

        /** @var AffiliationRepositoryInterface&Mockery\MockInterface $affiliationRepository */
        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findByAgencyAccount')
            ->once()
            ->with(Mockery::on(fn (AccountIdentifier $id) => (string) $id === (string) $agencyAccountId), AffiliationStatus::ACTIVE)
            ->andReturn([$affiliation]);

        $wiki = Mockery::mock(\Source\Wiki\Wiki\Domain\Entity\Wiki::class);
        $wiki->shouldReceive('wikiIdentifier')->andReturn($talentWikiId);

        /** @var WikiRepositoryInterface&Mockery\MockInterface $wikiRepository */
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByOwnerAccountId')
            ->once()
            ->with($talentAccountId, ResourceType::TALENT)
            ->andReturn($wiki);

        $resolver = new AffiliationConditionValueResolver($affiliationRepository, $wikiRepository);

        $this->assertSame([(string) $talentWikiId], $resolver->resolve(ConditionValue::PRINCIPAL_AFFILIATED_TALENT_IDS, $principal));
    }

    public function testResolveExistingPrincipalValuesFallsBackToPrincipalResolver(): void
    {
        $agencyId = StrTestHelper::generateUuid();
        $principal = $this->principal($agencyId);
        /** @var AffiliationRepositoryInterface $affiliationRepository */
        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        /** @var WikiRepositoryInterface $wikiRepository */
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $resolver = new AffiliationConditionValueResolver(
            $affiliationRepository,
            $wikiRepository,
        );

        $this->assertSame($agencyId, $resolver->resolve(ConditionValue::PRINCIPAL_AGENCY_ID, $principal));
    }

    private function principal(?string $agencyId): Principal
    {
        return new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            $agencyId,
            [],
            [],
        );
    }

    private function activeAffiliation(
        AccountIdentifier $agencyAccountId,
        AccountIdentifier $talentAccountId,
    ): Affiliation {
        return new Affiliation(
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            $agencyAccountId,
            $talentAccountId,
            $agencyAccountId,
            AffiliationStatus::ACTIVE,
            null,
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable(),
            null,
        );
    }
}
