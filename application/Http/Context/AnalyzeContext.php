<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;

readonly class AnalyzeContext
{
    public function __construct(public PrincipalIdentifier $principalIdentifier)
    {
    }
}
