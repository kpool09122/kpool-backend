<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Service;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\ValueObject\ConditionValue;

interface ConditionValueResolverInterface
{
    /**
     * @return string|string[]|bool|null
     */
    public function resolve(ConditionValue|string|bool $value, Principal $principal): string|array|bool|null;
}
