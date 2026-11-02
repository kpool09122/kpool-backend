<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Factory;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Shared\Domain\ValueObject\VisitorLocation;
use Source\Wiki\Wiki\Domain\Entity\WikiHistory;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;

interface WikiHistoryFactoryInterface
{
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
        VisitorLocation      $visitorLocation,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
        ?Language $language = null,
        ?ResourceType $resourceType = null,
        ?IdentityIdentifier $actorIdentityIdentifier = null,
    ): WikiHistory;
}
