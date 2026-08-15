<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Service;

use Application\Models\Account\Principal as AccountPrincipalEloquent;
use Application\Models\Wiki\Wiki as WikiEloquent;
use Illuminate\Support\Facades\DB;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Application\Service\PrincipalWikiScopeResolverInterface;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;

readonly class PrincipalWikiScopeResolver implements PrincipalWikiScopeResolverInterface
{
    public function __construct(
        private AffiliationRepositoryInterface $affiliationRepository,
        private WikiRepositoryInterface $wikiRepository,
    ) {
    }

    /** @return string[] */
    public function agencyWikiIdentifiers(Principal $principal): array
    {
        return $this->ownedWikiIdentifiers($principal, ResourceType::AGENCY);
    }

    /** @return string[] */
    public function groupWikiIdentifiers(Principal $principal): array
    {
        return $this->ownedWikiIdentifiers($principal, ResourceType::GROUP);
    }

    /** @return string[] */
    public function talentGroupWikiIdentifiers(Principal $principal): array
    {
        $talentWikiIdentifiers = $this->talentWikiIdentifiers($principal);
        if (empty($talentWikiIdentifiers)) {
            return [];
        }

        return DB::table('wiki_talent_basic_groups')
            ->whereIn('wiki_id', $talentWikiIdentifiers)
            ->pluck('group_identifier')
            ->map(static fn (mixed $id): string => (string) $id)
            ->unique()
            ->values()
            ->all();
    }

    /** @return string[] */
    public function talentWikiIdentifiers(Principal $principal): array
    {
        return $this->ownedWikiIdentifiers($principal, ResourceType::TALENT);
    }

    /** @return string[] */
    public function affiliatedTalentWikiIdentifiers(Principal $principal): array
    {
        $talentWikiIdentifiers = [];

        foreach ($this->accountIdentifiers($principal) as $accountIdentifier) {
            $affiliations = $this->affiliationRepository->findByAgencyAccount(
                new AccountIdentifier($accountIdentifier),
                AffiliationStatus::ACTIVE,
            );

            foreach ($affiliations as $affiliation) {
                $wiki = $this->wikiRepository->findByOwnerAccountId(
                    $affiliation->talentAccountIdentifier(),
                    ResourceType::TALENT,
                );

                if ($wiki !== null) {
                    $talentWikiIdentifiers[(string) $wiki->wikiIdentifier()] = (string) $wiki->wikiIdentifier();
                }
            }
        }

        return array_values($talentWikiIdentifiers);
    }

    /** @return string[] */
    private function ownedWikiIdentifiers(Principal $principal, ResourceType $resourceType): array
    {
        $accountIdentifiers = $this->accountIdentifiers($principal);
        if (empty($accountIdentifiers)) {
            return [];
        }

        return WikiEloquent::query()
            ->whereIn('owner_account_id', $accountIdentifiers)
            ->where('resource_type', $resourceType->value)
            ->pluck('id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all();
    }

    /** @return string[] */
    private function accountIdentifiers(Principal $principal): array
    {
        return AccountPrincipalEloquent::query()
            ->where('identity_id', (string) $principal->identityIdentifier())
            ->pluck('account_id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->unique()
            ->values()
            ->all();
    }
}
