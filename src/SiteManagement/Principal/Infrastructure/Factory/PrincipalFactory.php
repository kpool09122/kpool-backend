<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

readonly class PrincipalFactory implements PrincipalFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    public function create(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier): Principal
    {
        return new Principal(new PrincipalIdentifier($this->uuidGenerator->generate()), $identityIdentifier, $accountIdentifier);
    }
}
