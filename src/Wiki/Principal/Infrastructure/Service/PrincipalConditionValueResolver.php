<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Service;

use Source\Wiki\Principal\Application\Service\PrincipalWikiScopeResolverInterface;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Service\ConditionValueResolverInterface;
use Source\Wiki\Principal\Domain\ValueObject\ConditionValue;

class PrincipalConditionValueResolver implements ConditionValueResolverInterface
{
    public function __construct(private readonly PrincipalWikiScopeResolverInterface $principalWikiScopeResolver)
    {
    }

    /**
     * @return string|string[]|bool
     */
    public function resolve(ConditionValue|string|bool $value, Principal $principal): string|array|bool
    {
        if (! $value instanceof ConditionValue) {
            return $value;
        }

        return match ($value) {
            ConditionValue::PRINCIPAL_AGENCY_WIKI_IDENTIFIERS => $this->principalWikiScopeResolver->agencyWikiIdentifiers($principal),
            ConditionValue::PRINCIPAL_GROUP_WIKI_IDENTIFIERS => $this->principalWikiScopeResolver->groupWikiIdentifiers($principal),
            ConditionValue::PRINCIPAL_TALENT_GROUP_WIKI_IDENTIFIERS => $this->principalWikiScopeResolver->talentGroupWikiIdentifiers($principal),
            ConditionValue::PRINCIPAL_TALENT_WIKI_IDENTIFIERS => $this->principalWikiScopeResolver->talentWikiIdentifiers($principal),
            ConditionValue::PRINCIPAL_ID => (string) $principal->principalIdentifier(),
            ConditionValue::PRINCIPAL_AFFILIATED_TALENT_WIKI_IDENTIFIERS => $this->principalWikiScopeResolver->affiliatedTalentWikiIdentifiers($principal),
        };
    }
}
