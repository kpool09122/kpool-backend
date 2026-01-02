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

interface MergeAgencyInputPort
{
    public function agencyIdentifier(): AgencyIdentifier;

    public function name(): AgencyName;

    public function CEO(): CEO;

    public function foundedIn(): ?FoundedIn;

    public function description(): Description;

    public function principalIdentifier(): PrincipalIdentifier;

    public function mergedAt(): DateTimeImmutable;
}
