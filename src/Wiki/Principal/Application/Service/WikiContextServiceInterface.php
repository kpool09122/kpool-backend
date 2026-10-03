<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Service;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface WikiContextServiceInterface
{
    public function forget(IdentityIdentifier $identityIdentifier): void;
}
