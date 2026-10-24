<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\Service;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Shared\Domain\ValueObject\VisitorLocation;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Entity\Wiki;

interface WikiPublicationServiceInterface
{
    /** @throws WikiNotFoundException */
    public function publish(DraftWiki $wiki, Version $publishVersion, PrincipalIdentifier $principalIdentifier, VisitorLocation $visitorLocation, ?Wiki $publishedWiki = null): Wiki;
}
