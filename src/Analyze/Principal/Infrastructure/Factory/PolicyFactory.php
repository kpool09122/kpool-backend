<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Infrastructure\Factory;

use Source\Analyze\Principal\Domain\Entity\Policy;
use Source\Analyze\Principal\Domain\Factory\PolicyFactoryInterface;
use Source\Analyze\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\Statement;
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
