<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\CreateAgency;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;

interface CreateAgencyInputPort
{
    public function publishedAgencyIdentifier(): ?AgencyIdentifier;

    public function language(): Language;

    public function name(): AgencyName;

    public function slug(): Slug;

    public function CEO(): CEO;

    public function foundedIn(): ?FoundedIn;

    public function description(): Description;

    public function principalIdentifier(): PrincipalIdentifier;
}
