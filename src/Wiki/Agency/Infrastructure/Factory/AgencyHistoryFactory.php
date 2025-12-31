<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Agency\Domain\Entity\AgencyHistory;
use Source\Wiki\Agency\Domain\Factory\AgencyHistoryFactoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyHistoryIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class AgencyHistoryFactory implements AgencyHistoryFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    public function create(
        PrincipalIdentifier $editorIdentifier,
        ?PrincipalIdentifier $submitterIdentifier,
        ?AgencyIdentifier $agencyIdentifier,
        ?AgencyIdentifier $draftAgencyIdentifier,
        ?ApprovalStatus $fromStatus,
        ?ApprovalStatus $toStatus,
        AgencyName $subjectName,
    ): AgencyHistory {
        return new AgencyHistory(
            new AgencyHistoryIdentifier($this->generator->generate()),
            $editorIdentifier,
            $submitterIdentifier,
            $agencyIdentifier,
            $draftAgencyIdentifier,
            $fromStatus,
            $toStatus,
            $subjectName,
            new DateTimeImmutable('now'),
        );
    }
}
