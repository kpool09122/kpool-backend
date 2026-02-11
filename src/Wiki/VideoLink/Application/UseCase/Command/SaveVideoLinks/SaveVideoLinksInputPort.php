<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface SaveVideoLinksInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function resourceType(): ResourceType;

    public function wikiIdentifier(): WikiIdentifier;

    /**
     * @return VideoLinkData[]
     */
    public function videoLinks(): array;
}
