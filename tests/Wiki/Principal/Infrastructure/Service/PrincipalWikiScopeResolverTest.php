<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Infrastructure\Service\PrincipalWikiScopeResolver;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PrincipalWikiScopeResolverTest extends TestCase
{
    #[Group('useDb')]
    public function testResolveOwnedWikiIdentifiersFromPrincipalIdentityAccounts(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $agencyWikiIdentifier = StrTestHelper::generateUuid();
        $groupWikiIdentifier = StrTestHelper::generateUuid();
        $talentGroupWikiIdentifier = StrTestHelper::generateUuid();
        $talentWikiIdentifier = StrTestHelper::generateUuid();

        CreateIdentity::create($identityIdentifier);
        CreateAccount::create((string) $accountIdentifier);
        $this->createAccountPrincipal($identityIdentifier, $accountIdentifier);
        CreateWiki::create($agencyWikiIdentifier, ResourceType::AGENCY->value, [
            'owner_account_id' => (string) $accountIdentifier,
            'slug' => 'agency-' . $agencyWikiIdentifier,
        ]);
        CreateWiki::create($groupWikiIdentifier, ResourceType::GROUP->value, [
            'owner_account_id' => (string) $accountIdentifier,
            'slug' => 'group-' . $groupWikiIdentifier,
        ]);
        CreateWiki::create($talentGroupWikiIdentifier, ResourceType::GROUP->value, [
            'slug' => 'talent-group-' . $talentGroupWikiIdentifier,
        ]);
        CreateWiki::create(
            $talentWikiIdentifier,
            ResourceType::TALENT->value,
            [
                'owner_account_id' => (string) $accountIdentifier,
                'slug' => 'talent-' . $talentWikiIdentifier,
            ],
            [
                'group_identifiers' => json_encode([$talentGroupWikiIdentifier]),
            ],
        );

        /** @var AffiliationRepositoryInterface&Mockery\MockInterface $affiliationRepository */
        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        /** @var WikiRepositoryInterface&Mockery\MockInterface $wikiRepository */
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $resolver = new PrincipalWikiScopeResolver($affiliationRepository, $wikiRepository);
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $identityIdentifier);

        $this->assertSame([$agencyWikiIdentifier], $resolver->agencyWikiIdentifiers($principal));
        $this->assertSame([$groupWikiIdentifier], $resolver->groupWikiIdentifiers($principal));
        $this->assertSame([$talentGroupWikiIdentifier], $resolver->talentGroupWikiIdentifiers($principal));
        $this->assertSame([$talentWikiIdentifier], $resolver->talentWikiIdentifiers($principal));
    }

    #[Group('useDb')]
    public function testResolveAffiliatedTalentWikiIdentifiersFromPrincipalIdentityAccounts(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentWikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $identityIdentifier);

        CreateIdentity::create($identityIdentifier);
        CreateAccount::create((string) $agencyAccountIdentifier);
        $this->createAccountPrincipal($identityIdentifier, $agencyAccountIdentifier);
        $affiliation = new Affiliation(
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $agencyAccountIdentifier,
            AffiliationStatus::ACTIVE,
            null,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            null,
        );

        /** @var AffiliationRepositoryInterface&Mockery\MockInterface $affiliationRepository */
        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findByAgencyAccount')
            ->once()
            ->with(
                Mockery::on(static fn (AccountIdentifier $id): bool => (string) $id === (string) $agencyAccountIdentifier),
                AffiliationStatus::ACTIVE,
            )
            ->andReturn([$affiliation]);

        $wiki = Mockery::mock(\Source\Wiki\Wiki\Domain\Entity\Wiki::class);
        $wiki->shouldReceive('wikiIdentifier')->andReturn($talentWikiIdentifier);
        /** @var WikiRepositoryInterface&Mockery\MockInterface $wikiRepository */
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByOwnerAccountId')
            ->once()
            ->with($talentAccountIdentifier, ResourceType::TALENT)
            ->andReturn($wiki);

        $resolver = new PrincipalWikiScopeResolver($affiliationRepository, $wikiRepository);

        $this->assertSame([(string) $talentWikiIdentifier], $resolver->affiliatedTalentWikiIdentifiers($principal));
    }

    private function createAccountPrincipal(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier): void
    {
        DB::table('account_principals')->insert([
            'id' => StrTestHelper::generateUuid(),
            'identity_id' => (string) $identityIdentifier,
            'account_id' => (string) $accountIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
