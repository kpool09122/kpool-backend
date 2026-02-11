<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Service;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface ContributionPointServiceInterface
{
    /**
     * Grant contribution points to principals when a wiki is published.
     *
     * @param PrincipalIdentifier|null $editorIdentifier null for translation articles
     * @param PrincipalIdentifier $approverIdentifier the user who approved
     * @param PrincipalIdentifier|null $mergerIdentifier the user who merged
     * @param ResourceType $resourceType agency, talent, group, or song
     * @param WikiIdentifier $wikiIdentifier the ID of the wiki resource
     * @param bool $isNewCreation true for new creation, false for update
     */
    public function grantPoints(
        ?PrincipalIdentifier $editorIdentifier,
        PrincipalIdentifier  $approverIdentifier,
        ?PrincipalIdentifier $mergerIdentifier,
        ResourceType         $resourceType,
        WikiIdentifier       $wikiIdentifier,
        bool                 $isNewCreation,
    ): void;
}
