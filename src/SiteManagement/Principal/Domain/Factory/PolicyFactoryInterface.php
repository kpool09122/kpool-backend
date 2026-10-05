<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\Factory;

use Source\SiteManagement\Principal\Domain\Entity\Policy;
use Source\SiteManagement\Principal\Domain\ValueObject\Statement;

interface PolicyFactoryInterface
{
    /** @param Statement[] $statements */
    public function create(string $name, array $statements): Policy;
}
