<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Factory;

use DateTimeImmutable;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\Entity\VideoWatchRequest;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface VideoWatchRequestFactoryInterface
{
    public function create(VideoLinkIdentifier $videoLinkIdentifier, WikiIdentifier $wikiIdentifier, PrincipalIdentifier $principalIdentifier, DateTimeImmutable $sentAt): VideoWatchRequest;
}
