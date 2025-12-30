<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Factory;

use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Entity\TalentSnapshot;

interface TalentSnapshotFactoryInterface
{
    public function create(Talent $talent): TalentSnapshot;
}
