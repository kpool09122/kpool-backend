<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials;

use Source\Wiki\Image\Application\UseCase\Query\FreeMaterialSiteReadModel;

interface SearchFreeMaterialsOutputPort
{
    /** @param list<FreeMaterialSiteReadModel> $sites */
    public function output(array $sites): void;
}
