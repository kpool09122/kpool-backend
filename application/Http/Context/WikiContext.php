<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class WikiContext
{
    public function __construct(
        public PrincipalIdentifier $principalIdentifier,
    ) {
    }
}
