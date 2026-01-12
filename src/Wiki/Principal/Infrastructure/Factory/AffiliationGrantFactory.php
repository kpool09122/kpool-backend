<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Principal\Domain\Entity\AffiliationGrant;
use Source\Wiki\Principal\Domain\Factory\AffiliationGrantFactoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantType;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;

readonly class AffiliationGrantFactory implements AffiliationGrantFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    public function create(
        AffiliationIdentifier $affiliationIdentifier,
        PolicyIdentifier $policyIdentifier,
        RoleIdentifier $roleIdentifier,
        PrincipalGroupIdentifier $principalGroupIdentifier,
        AffiliationGrantType $type,
    ): AffiliationGrant {
        return new AffiliationGrant(
            new AffiliationGrantIdentifier($this->generator->generate()),
            $affiliationIdentifier,
            $policyIdentifier,
            $roleIdentifier,
            $principalGroupIdentifier,
            $type,
            new DateTimeImmutable(),
        );
    }
}
