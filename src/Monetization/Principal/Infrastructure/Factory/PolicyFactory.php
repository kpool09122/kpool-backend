<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Infrastructure\Factory;

use Source\Monetization\Principal\Domain\Entity\Policy;
use Source\Monetization\Principal\Domain\Factory\PolicyFactoryInterface;
use Source\Monetization\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\Statement;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class PolicyFactory implements PolicyFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    /** @param Statement[] $statements */
    public function create(string $name, array $statements, ?AccountIdentifier $accountIdentifier = null): Policy
    {
        return new Policy(new PolicyIdentifier($this->uuidGenerator->generate()), $name, $statements, $accountIdentifier);
    }
}
