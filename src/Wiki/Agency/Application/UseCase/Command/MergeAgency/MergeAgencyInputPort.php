<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\MergeAgency;

use DateTimeImmutable;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\CEO;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\FoundedIn;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;

interface MergeAgencyInputPort
{
    public function agencyIdentifier(): AgencyIdentifier;

    public function name(): Name;

    public function CEO(): CEO;

    public function foundedIn(): ?FoundedIn;

    public function description(): Description;

    public function principalIdentifier(): PrincipalIdentifier;

    public function mergedAt(): DateTimeImmutable;
}
