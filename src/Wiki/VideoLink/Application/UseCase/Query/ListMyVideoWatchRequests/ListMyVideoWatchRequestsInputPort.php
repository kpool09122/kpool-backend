<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoWatchRequests;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;

interface ListMyVideoWatchRequestsInputPort
{
    /** @return list<VideoLinkIdentifier> */
    public function videoLinkIdentifiers(): array;

    public function principalIdentifier(): PrincipalIdentifier;
}
