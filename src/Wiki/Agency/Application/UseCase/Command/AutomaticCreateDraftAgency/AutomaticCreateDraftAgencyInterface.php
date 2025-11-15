<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\AutomaticCreateDraftAgency;

use Source\Wiki\Agency\Domain\Entity\DraftAgency;

interface AutomaticCreateDraftAgencyInterface
{
    /**
     * @param AutomaticCreateDraftAgencyInputPort $input
     * @return DraftAgency
     */
    public function process(AutomaticCreateDraftAgencyInputPort $input): DraftAgency;
}
