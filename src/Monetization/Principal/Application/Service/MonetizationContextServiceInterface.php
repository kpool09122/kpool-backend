<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Application\Service;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface MonetizationContextServiceInterface
{
    public function forgetForAccount(?AccountIdentifier $accountIdentifier): void;

    public function forget(IdentityIdentifier $identityIdentifier): void;
}
