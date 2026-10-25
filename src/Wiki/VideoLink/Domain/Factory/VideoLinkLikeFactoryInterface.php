<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Factory;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\Entity\VideoLinkLike;

interface VideoLinkLikeFactoryInterface
{
    public function create(VideoLinkIdentifier $videoLinkIdentifier, PrincipalIdentifier $principalIdentifier): VideoLinkLike;
}
