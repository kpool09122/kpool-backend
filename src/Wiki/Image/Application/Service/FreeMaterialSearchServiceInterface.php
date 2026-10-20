<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\Service;

use Source\Wiki\Image\Application\DTO\MaterialSearchContext;
use Source\Wiki\Image\Application\Exception\MaterialSearchFailedException;
use Source\Wiki\Image\Application\UseCase\Query\FreeMaterialSiteReadModel;

interface FreeMaterialSearchServiceInterface
{
    /**
     * @return list<FreeMaterialSiteReadModel>
     * @throws MaterialSearchFailedException
     */
    public function search(MaterialSearchContext $context): array;
}
