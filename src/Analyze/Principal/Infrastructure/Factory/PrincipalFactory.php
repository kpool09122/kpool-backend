<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Infrastructure\Factory;

use Source\Analyze\Principal\Domain\Entity\Principal;
use Source\Analyze\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class PrincipalFactory implements PrincipalFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    public function create(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier): Principal
    {
        return new Principal(new PrincipalIdentifier($this->uuidGenerator->generate()), $identityIdentifier, $accountIdentifier, PrincipalStatus::ACTIVE);
    }
}
