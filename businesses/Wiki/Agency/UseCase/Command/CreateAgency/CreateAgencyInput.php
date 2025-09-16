<?php

namespace Businesses\Wiki\Agency\UseCase\Command\CreateAgency;

use Businesses\Wiki\Agency\Domain\ValueObject\AgencyName;
use Businesses\Wiki\Agency\Domain\ValueObject\CEO;
use Businesses\Wiki\Agency\Domain\ValueObject\Description;
use Businesses\Wiki\Agency\Domain\ValueObject\FoundedIn;

readonly class CreateAgencyInput implements CreateAgencyInputPort
{
    /**
     * @param AgencyName $name
     * @param CEO $CEO
     * @param FoundedIn $foundedIn
     * @param Description $description
     */
    public function __construct(
        private AgencyName $name,
        private CEO $CEO,
        private ?FoundedIn $foundedIn,
        private Description $description,
    ) {
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
