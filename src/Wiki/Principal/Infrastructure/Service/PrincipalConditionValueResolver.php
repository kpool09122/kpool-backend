<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Service;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Service\ConditionValueResolverInterface;
use Source\Wiki\Principal\Domain\ValueObject\ConditionValue;

class PrincipalConditionValueResolver implements ConditionValueResolverInterface
{
    /**
     * @return string|string[]|bool|null
     */
    public function resolve(ConditionValue|string|bool $value, Principal $principal): string|array|bool|null
    {
        if (! $value instanceof ConditionValue) {
            return $value;
        }

        return match ($value) {
            ConditionValue::PRINCIPAL_AGENCY_ID => $principal->agencyId(),
            ConditionValue::PRINCIPAL_WIKI_GROUP_IDS => $principal->groupIds(),
            ConditionValue::PRINCIPAL_TALENT_IDS => $principal->talentIds(),
            ConditionValue::PRINCIPAL_ID => (string) $principal->principalIdentifier(),
            ConditionValue::PRINCIPAL_AFFILIATED_TALENT_IDS => [],
        };
    }
}
