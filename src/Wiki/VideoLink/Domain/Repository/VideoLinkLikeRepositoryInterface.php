<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Repository;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\VideoLink\Domain\Entity\VideoLinkLike;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;

interface VideoLinkLikeRepositoryInterface
{
    public function findByVideoLinkAndPrincipal(VideoLinkIdentifier $videoLinkIdentifier, PrincipalIdentifier $principalIdentifier): ?VideoLinkLike;

    public function save(VideoLinkLike $videoLinkLike): void;

    public function deleteByVideoLinkAndPrincipal(VideoLinkIdentifier $videoLinkIdentifier, PrincipalIdentifier $principalIdentifier): void;
}
