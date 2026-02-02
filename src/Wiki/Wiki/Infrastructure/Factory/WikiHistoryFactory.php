<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\WikiHistory;
use Source\Wiki\Wiki\Domain\Factory\WikiHistoryFactoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiHistoryIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class WikiHistoryFactory implements WikiHistoryFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    public function create(
        HistoryActionType    $actionType,
        PrincipalIdentifier  $actorIdentifier,
        ?PrincipalIdentifier $submitterIdentifier,
        ?WikiIdentifier      $wikiIdentifier,
        ?DraftWikiIdentifier $draftWikiIdentifier,
        ?ApprovalStatus      $fromStatus,
        ?ApprovalStatus      $toStatus,
        ?Version             $fromVersion,
        ?Version             $toVersion,
        Name                 $subjectName,
    ): WikiHistory {
        return new WikiHistory(
            new WikiHistoryIdentifier($this->generator->generate()),
            $actionType,
            $actorIdentifier,
            $submitterIdentifier,
            $wikiIdentifier,
            $draftWikiIdentifier,
            $fromStatus,
            $toStatus,
            $fromVersion,
            $toVersion,
            $subjectName,
            new DateTimeImmutable('now'),
        );
    }
}
