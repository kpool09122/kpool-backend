<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\AutomaticCreateDraftAgency;

use Source\Wiki\Agency\Domain\ValueObject\AutomaticDraftAgencyCreationPayload;
use Source\Wiki\Shared\Domain\Entity\Principal;

readonly class AutomaticCreateDraftAgencyInput implements AutomaticCreateDraftAgencyInputPort
{
    public function __construct(
        private AutomaticDraftAgencyCreationPayload $payload,
        private Principal $principal,
    ) {
    }

    public function payload(): AutomaticDraftAgencyCreationPayload
    {
        return $this->payload;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
