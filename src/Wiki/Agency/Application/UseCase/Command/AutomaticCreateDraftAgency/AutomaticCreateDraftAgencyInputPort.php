<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\AutomaticCreateDraftAgency;

use Source\Wiki\Agency\Domain\ValueObject\AutomaticDraftAgencyCreationPayload;
use Source\Wiki\Shared\Domain\Entity\Principal;

interface AutomaticCreateDraftAgencyInputPort
{
    public function payload(): AutomaticDraftAgencyCreationPayload;

    public function principal(): Principal;
}
