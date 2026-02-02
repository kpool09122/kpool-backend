<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;

final readonly class AutoAgencyCreationPayload
{
    public function __construct(
        private Language $language,
        private Name     $name,
    ) {
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function name(): Name
    {
        return $this->name;
    }
}
