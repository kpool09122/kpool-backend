<?php

declare(strict_types=1);

namespace Source\Account\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Account\Domain\Entity\DelegationPermission;
use Source\Account\Domain\Factory\DelegationPermissionFactoryInterface;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Domain\ValueObject\DelegationPermissionIdentifier;
use Source\Account\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class DelegationPermissionFactory implements DelegationPermissionFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    public function create(
        IdentityGroupIdentifier $identityGroupIdentifier,
        AccountIdentifier $targetAccountIdentifier,
        AffiliationIdentifier $affiliationIdentifier,
    ): DelegationPermission {
        return new DelegationPermission(
            new DelegationPermissionIdentifier($this->generator->generate()),
            $identityGroupIdentifier,
            $targetAccountIdentifier,
            $affiliationIdentifier,
            new DateTimeImmutable(),
        );
    }
}
