<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Entity;

use DateTimeImmutable;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkLikeIdentifier;

readonly class VideoLinkLike
{
    public function __construct(
        private VideoLinkLikeIdentifier $videoLinkLikeIdentifier,
        private VideoLinkIdentifier $videoLinkIdentifier,
        private PrincipalIdentifier $principalIdentifier,
        private DateTimeImmutable $createdAt,
    ) {
    }

    public function videoLinkLikeIdentifier(): VideoLinkLikeIdentifier
    {
        return $this->videoLinkLikeIdentifier;
    }

    public function videoLinkIdentifier(): VideoLinkIdentifier
    {
        return $this->videoLinkIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
