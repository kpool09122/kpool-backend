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
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;

readonly class AgencyHistoryFactory implements AgencyHistoryFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        EditorIdentifier $editorIdentifier,
        ?EditorIdentifier $submitterIdentifier,
        ?AgencyIdentifier $agencyIdentifier,
        ?AgencyIdentifier $draftAgencyIdentifier,
        ?ApprovalStatus $fromStatus,
        ?ApprovalStatus $toStatus,
        AgencyName $subjectName,
    ): AgencyHistory {
        return new AgencyHistory(
            new AgencyHistoryIdentifier($this->ulidGenerator->generate()),
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
