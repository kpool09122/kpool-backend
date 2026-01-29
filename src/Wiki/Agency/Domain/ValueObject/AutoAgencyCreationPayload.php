<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\Language;

final readonly class AutoAgencyCreationPayload
{
    public function __construct(
        private Language                   $language,
        private AgencyName                 $name,
    ) {
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function name(): AgencyName
    {
        return $this->name;
    }
}
