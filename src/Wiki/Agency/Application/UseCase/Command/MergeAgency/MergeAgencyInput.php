<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\MergeAgency;

use DateTimeImmutable;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class MergeAgencyInput implements MergeAgencyInputPort
{
    /**
     * @param AgencyIdentifier $agencyIdentifier
     * @param AgencyName $name
     * @param CEO $CEO
     * @param ?FoundedIn $foundedIn
     * @param Description $description
     * @param PrincipalIdentifier $principalIdentifier
     * @param DateTimeImmutable $mergedAt
     */
    public function __construct(
        private AgencyIdentifier    $agencyIdentifier,
        private AgencyName          $name,
        private CEO                 $CEO,
        private ?FoundedIn          $foundedIn,
        private Description         $description,
        private PrincipalIdentifier $principalIdentifier,
        private DateTimeImmutable   $mergedAt,
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

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function mergedAt(): DateTimeImmutable
    {
        return $this->mergedAt;
    }
}
