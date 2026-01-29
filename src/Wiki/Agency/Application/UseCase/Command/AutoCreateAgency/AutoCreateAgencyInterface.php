<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\AutoCreateAgency;

use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface AutoCreateAgencyInterface
{
    /**
     * @param AutoCreateAgencyInputPort $input
     * @return DraftAgency
     * @throws PrincipalNotFoundException
     * @throws DisallowedException
     */
    public function process(AutoCreateAgencyInputPort $input): DraftAgency;
}
