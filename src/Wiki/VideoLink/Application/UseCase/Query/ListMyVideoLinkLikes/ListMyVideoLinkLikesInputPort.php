<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;

interface ListMyVideoLinkLikesInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    /** @return list<VideoLinkIdentifier> */
    public function videoLinkIdentifiers(): array;
}
