<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Entity;

use DateTimeImmutable;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoWatchRequestIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

final readonly class VideoWatchRequest
{
    public function __construct(
        private VideoWatchRequestIdentifier $videoWatchRequestIdentifier,
        private VideoLinkIdentifier $videoLinkIdentifier,
        private WikiIdentifier $wikiIdentifier,
        private PrincipalIdentifier $principalIdentifier,
        private DateTimeImmutable $sentAt,
    ) {
    }

    public function videoWatchRequestIdentifier(): VideoWatchRequestIdentifier
    {
        return $this->videoWatchRequestIdentifier;
    }

    public function videoLinkIdentifier(): VideoLinkIdentifier
    {
        return $this->videoLinkIdentifier;
    }

    public function wikiIdentifier(): WikiIdentifier
    {
        return $this->wikiIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function sentAt(): DateTimeImmutable
    {
        return $this->sentAt;
    }
}
