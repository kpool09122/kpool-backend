<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;

readonly class ActorContext
{
    public function __construct(
        public IdentityIdentifier $identityIdentifier,
        public Language $language,
        public ?DelegationIdentifier $delegationIdentifier,
        public ?IdentityIdentifier $originalIdentityIdentifier,
    ) {
    }
}
