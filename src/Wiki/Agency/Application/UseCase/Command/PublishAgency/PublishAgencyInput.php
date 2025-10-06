<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\PublishAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;

readonly class PublishAgencyInput implements PublishAgencyInputPort
{
    public function __construct(
        private AgencyIdentifier  $agencyIdentifier,
        private ?AgencyIdentifier $publishedAgencyIdentifier,
        private Principal $principal,
    ) {
    }

    public function agencyIdentifier(): AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function publishedAgencyIdentifier(): ?AgencyIdentifier
    {
        return $this->publishedAgencyIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
