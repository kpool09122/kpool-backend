<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Principal\Domain\Entity\Policy;
use Source\Wiki\Principal\Domain\Factory\PolicyFactoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;

readonly class PolicyFactory implements PolicyFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function create(
        string $name,
        array $statements,
        bool $isSystemPolicy,
    ): Policy {
        return new Policy(
            new PolicyIdentifier($this->generator->generate()),
            $name,
            $statements,
            $isSystemPolicy,
            new DateTimeImmutable(),
        );
    }
}
