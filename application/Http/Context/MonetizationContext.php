<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;

readonly class MonetizationContext
{
    public function __construct(
        public PrincipalIdentifier $principalIdentifier,
    ) {
    }
}
