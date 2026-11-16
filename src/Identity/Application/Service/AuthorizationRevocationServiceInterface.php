<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Domain\Entity\AuthorizationConsent;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface AuthorizationRevocationServiceInterface
{
    public function revoke(AuthorizationConsent $consent): void;

    public function revokeAndDeleteForIdentity(IdentityIdentifier $identityIdentifier): void;
}
