<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface SaveVideoLinksInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function resourceType(): ResourceType;

    public function resourceIdentifier(): ResourceIdentifier;

    /**
     * @return VideoLinkData[]
     */
    public function videoLinks(): array;
}
