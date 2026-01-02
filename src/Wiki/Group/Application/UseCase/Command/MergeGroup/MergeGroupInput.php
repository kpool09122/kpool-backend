<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\MergeGroup;

use DateTimeImmutable;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class MergeGroupInput implements MergeGroupInputPort
{
    /**
     * @param GroupIdentifier $groupIdentifier
     * @param GroupName $name
     * @param AgencyIdentifier $agencyIdentifier
     * @param Description $description
     * @param PrincipalIdentifier $principalIdentifier
     * @param DateTimeImmutable $mergedAt
     */
    public function __construct(
        private GroupIdentifier     $groupIdentifier,
        private GroupName           $name,
        private AgencyIdentifier    $agencyIdentifier,
        private Description         $description,
        private PrincipalIdentifier $principalIdentifier,
        private DateTimeImmutable   $mergedAt,
    ) {
    }

    public function groupIdentifier(): GroupIdentifier
    {
        return $this->groupIdentifier;
    }

    public function name(): GroupName
    {
        return $this->name;
    }

    public function agencyIdentifier(): AgencyIdentifier
    {
        return $this->agencyIdentifier;
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
