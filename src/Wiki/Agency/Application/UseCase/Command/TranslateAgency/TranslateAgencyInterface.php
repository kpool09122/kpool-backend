<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\TranslateAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;

interface TranslateAgencyInterface
{
    /**
     * @param TranslateAgencyInputPort $input
     * @return DraftAgency[]
     * @throws AgencyNotFoundException
     */
    public function process(TranslateAgencyInputPort $input): array;
}
