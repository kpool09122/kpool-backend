<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\AutomaticCreateDraftAgency;

use Source\Wiki\Agency\Domain\ValueObject\AutomaticDraftAgencyCreationPayload;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class AutomaticCreateDraftAgencyInput implements AutomaticCreateDraftAgencyInputPort
{
    public function __construct(
        private AutomaticDraftAgencyCreationPayload $payload,
        private PrincipalIdentifier $principalIdentifier,
    ) {
    }

    public function payload(): AutomaticDraftAgencyCreationPayload
    {
        return $this->payload;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
