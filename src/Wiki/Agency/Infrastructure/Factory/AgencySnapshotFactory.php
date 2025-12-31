<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Entity\AgencySnapshot;
use Source\Wiki\Agency\Domain\Factory\AgencySnapshotFactoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencySnapshotIdentifier;

readonly class AgencySnapshotFactory implements AgencySnapshotFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(Agency $agency): AgencySnapshot
    {
        return new AgencySnapshot(
            new AgencySnapshotIdentifier($this->ulidGenerator->generate()),
            $agency->agencyIdentifier(),
            $agency->translationSetIdentifier(),
            $agency->language(),
            $agency->name(),
            $agency->normalizedName(),
            $agency->CEO(),
            $agency->normalizedCEO(),
            $agency->foundedIn(),
            $agency->description(),
            $agency->version(),
            new DateTimeImmutable('now'),
        );
    }
}
