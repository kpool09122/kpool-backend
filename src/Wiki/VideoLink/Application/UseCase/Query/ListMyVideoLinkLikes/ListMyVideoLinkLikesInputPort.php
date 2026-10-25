<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;

interface ListMyVideoLinkLikesInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    /** @return list<VideoLinkIdentifier>|null */
    public function videoLinkIdentifiers(): ?array;

    public function page(): int;

    public function perPage(): int;
}
