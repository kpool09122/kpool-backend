<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Factory;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\ValueObject\GroupName;

interface GroupFactoryInterface
{
    public function create(
        Translation $translation,
        GroupName $name,
    ): Group;
}
