<?php

namespace Businesses\Wiki\Agency\UseCase\Command\EditAgency;

use Businesses\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Businesses\Wiki\Agency\Domain\ValueObject\AgencyName;
use Businesses\Wiki\Agency\Domain\ValueObject\CEO;
use Businesses\Wiki\Agency\Domain\ValueObject\Description;
use Businesses\Wiki\Agency\Domain\ValueObject\FoundedIn;

readonly class EditAgencyInput implements EditAgencyInputPort
{
    /**
     * @param AgencyIdentifier $agencyIdentifier
     * @param AgencyName $name
     * @param CEO $CEO
     * @param ?FoundedIn $foundedIn
     * @param Description $description
     */
    public function __construct(
        private AgencyIdentifier $agencyIdentifier,
        private AgencyName $name,
        private CEO $CEO,
        private ?FoundedIn $foundedIn,
        private Description $description,
    ) {
    }

    public function agencyIdentifier(): AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function name(): AgencyName
    {
        return $this->name;
    }

    public function CEO(): CEO
    {
        return $this->CEO;
    }

    public function foundedIn(): ?FoundedIn
    {
        return $this->foundedIn;
    }

    public function description(): Description
    {
        return $this->description;
    }
}
