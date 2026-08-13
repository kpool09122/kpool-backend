<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Service;

use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Service\ConditionValueResolverInterface;
use Source\Wiki\Principal\Domain\ValueObject\ConditionValue;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;

class AffiliationConditionValueResolver extends PrincipalConditionValueResolver implements ConditionValueResolverInterface
{
    public function __construct(
        private readonly AffiliationRepositoryInterface $affiliationRepository,
        private readonly WikiRepositoryInterface $wikiRepository,
    ) {
    }

    /**
     * @return string|string[]|bool|null
     */
    public function resolve(ConditionValue|string|bool $value, Principal $principal): string|array|bool|null
    {
        if ($value !== ConditionValue::PRINCIPAL_AFFILIATED_TALENT_IDS) {
            return parent::resolve($value, $principal);
        }

        $agencyId = $principal->agencyId();
        if ($agencyId === null) {
            return [];
        }

        $affiliations = $this->affiliationRepository->findByAgencyAccount(
            new AccountIdentifier($agencyId),
            AffiliationStatus::ACTIVE,
        );

        $talentIds = [];
        foreach ($affiliations as $affiliation) {
            $wiki = $this->wikiRepository->findByOwnerAccountId(
                $affiliation->talentAccountIdentifier(),
                ResourceType::TALENT,
            );
            if ($wiki !== null) {
                $talentIds[(string) $wiki->wikiIdentifier()] = (string) $wiki->wikiIdentifier();
            }
        }

        return array_values($talentIds);
    }
}
