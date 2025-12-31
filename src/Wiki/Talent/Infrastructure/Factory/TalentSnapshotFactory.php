<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Entity\TalentSnapshot;
use Source\Wiki\Talent\Domain\Factory\TalentSnapshotFactoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\TalentSnapshotIdentifier;

readonly class TalentSnapshotFactory implements TalentSnapshotFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    public function create(Talent $talent): TalentSnapshot
    {
        return new TalentSnapshot(
            new TalentSnapshotIdentifier($this->generator->generate()),
            $talent->talentIdentifier(),
            $talent->translationSetIdentifier(),
            $talent->language(),
            $talent->name(),
            $talent->realName(),
            $talent->agencyIdentifier(),
            $talent->groupIdentifiers(),
            $talent->birthday(),
            $talent->career(),
            $talent->imageLink(),
            $talent->relevantVideoLinks(),
            $talent->version(),
            new DateTimeImmutable('now'),
        );
    }
}
