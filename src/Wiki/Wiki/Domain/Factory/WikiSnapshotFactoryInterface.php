<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Factory;

use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Entity\WikiSnapshot;

interface WikiSnapshotFactoryInterface
{
    public function create(Wiki $wiki): WikiSnapshot;
}
