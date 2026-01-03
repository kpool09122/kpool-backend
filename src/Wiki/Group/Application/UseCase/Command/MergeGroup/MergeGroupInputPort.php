<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\MergeGroup;

use DateTimeImmutable;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface MergeGroupInputPort
{
    public function groupIdentifier(): GroupIdentifier;

    public function name(): GroupName;

    public function agencyIdentifier(): AgencyIdentifier;

    public function description(): Description;

    public function principalIdentifier(): PrincipalIdentifier;

    public function mergedAt(): DateTimeImmutable;
}
