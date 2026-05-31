<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedProfiles;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;

interface ListRelatedProfilesInputPort
{
    public function slug(): Slug;

    public function language(): Language;

    public function resourceType(): ResourceType;
}
