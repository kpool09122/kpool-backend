<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Service;

use Source\Wiki\Principal\Application\Service\PrincipalWikiScopeResolverInterface;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Service\ConditionValueResolverInterface;
use Source\Wiki\Principal\Domain\ValueObject\ConditionValue;

class AffiliationConditionValueResolver extends PrincipalConditionValueResolver implements ConditionValueResolverInterface
{
    public function __construct(PrincipalWikiScopeResolverInterface $principalWikiScopeResolver)
    {
        parent::__construct($principalWikiScopeResolver);
    }

    /**
     * @return string|string[]|bool
     */
    public function resolve(ConditionValue|string|bool $value, Principal $principal): string|array|bool
    {
        return parent::resolve($value, $principal);
    }
}
