<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Factory;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\VideoLink\Domain\Entity\VideoLinkLike;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;

interface VideoLinkLikeFactoryInterface
{
    public function create(VideoLinkIdentifier $videoLinkIdentifier, PrincipalIdentifier $principalIdentifier): VideoLinkLike;
}
