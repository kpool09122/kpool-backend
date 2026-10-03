<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Account\Principal\Domain\Entity\Policy;
use Source\Account\Principal\Domain\Factory\PolicyFactoryInterface;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class PolicyFactory implements PolicyFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $generator)
    {
    }

    public function create(string $name, array $statements, AccountIdentifier $accountIdentifier): Policy
    {
        return new Policy(
            new PolicyIdentifier($this->generator->generate()),
            $name,
            $statements,
            $accountIdentifier,
            new DateTimeImmutable(),
        );
    }
}
