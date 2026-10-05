<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\SiteManagement\Principal\Domain\Entity\Policy;
use Source\SiteManagement\Principal\Domain\Factory\PolicyFactoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\Statement;

readonly class PolicyFactory implements PolicyFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    /** @param Statement[] $statements */
    public function create(string $name, array $statements): Policy
    {
        return new Policy(new PolicyIdentifier($this->uuidGenerator->generate()), $name, $statements);
    }
}
