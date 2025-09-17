<?php

declare(strict_types=1);

namespace Businesses\Wiki\Group\Domain\Factory;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Group\Domain\Entity\Group;
use Businesses\Wiki\Group\Domain\ValueObject\GroupName;

interface GroupFactoryInterface
{
    public function create(
        Translation $translation,
        GroupName $name,
    ): Group;
}
