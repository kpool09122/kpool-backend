<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\AutomaticCreateDraftAgency;

use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface AutomaticCreateDraftAgencyInterface
{
    /**
     * @param AutomaticCreateDraftAgencyInputPort $input
     * @return DraftAgency
     * @throws PrincipalNotFoundException
     */
    public function process(AutomaticCreateDraftAgencyInputPort $input): DraftAgency;
}
