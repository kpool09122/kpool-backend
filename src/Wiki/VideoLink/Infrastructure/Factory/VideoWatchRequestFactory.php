<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\Entity\VideoWatchRequest;
use Source\Wiki\VideoLink\Domain\Factory\VideoWatchRequestFactoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoWatchRequestIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

final readonly class VideoWatchRequestFactory implements VideoWatchRequestFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    public function create(VideoLinkIdentifier $videoLinkIdentifier, WikiIdentifier $wikiIdentifier, PrincipalIdentifier $principalIdentifier, DateTimeImmutable $sentAt): VideoWatchRequest
    {
        return new VideoWatchRequest(new VideoWatchRequestIdentifier($this->uuidGenerator->generate()), $videoLinkIdentifier, $wikiIdentifier, $principalIdentifier, $sentAt);
    }
}
