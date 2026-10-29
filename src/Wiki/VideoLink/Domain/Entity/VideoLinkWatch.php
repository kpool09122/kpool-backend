<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkWatchIdentifier;

readonly class VideoLinkWatch
{
    public function __construct(
        private VideoLinkWatchIdentifier $videoLinkWatchIdentifier,
        private VideoLinkIdentifier $videoLinkIdentifier,
        private AccountIdentifier $ownerAccountIdentifier,
        private PrincipalIdentifier $principalIdentifier,
        private DateTimeImmutable $watchedAt,
    ) {
    }

    public function videoLinkWatchIdentifier(): VideoLinkWatchIdentifier
    {
        return $this->videoLinkWatchIdentifier;
    }

    public function videoLinkIdentifier(): VideoLinkIdentifier
    {
        return $this->videoLinkIdentifier;
    }

    public function ownerAccountIdentifier(): AccountIdentifier
    {
        return $this->ownerAccountIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function watchedAt(): DateTimeImmutable
    {
        return $this->watchedAt;
    }
}
