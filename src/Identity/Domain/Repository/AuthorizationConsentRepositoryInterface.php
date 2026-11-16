<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Repository;

use Source\Identity\Domain\Entity\AuthorizationConsent;
use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface AuthorizationConsentRepositoryInterface
{
    public function save(AuthorizationConsent $authorizationConsent): void;

    public function find(AuthorizationConsentIdentifier $identifier): ?AuthorizationConsent;

    /** @return list<AuthorizationConsent> */
    public function findByIdentityIdentifier(IdentityIdentifier $identityIdentifier): array;
}
