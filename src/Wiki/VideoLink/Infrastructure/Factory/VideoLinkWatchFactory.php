<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\Entity\VideoLinkWatch;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkWatchFactoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkWatchIdentifier;

readonly class VideoLinkWatchFactory implements VideoLinkWatchFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    public function create(VideoLinkIdentifier $videoLinkIdentifier, AccountIdentifier $ownerAccountIdentifier, PrincipalIdentifier $principalIdentifier): VideoLinkWatch
    {
        return new VideoLinkWatch(new VideoLinkWatchIdentifier($this->uuidGenerator->generate()), $videoLinkIdentifier, $ownerAccountIdentifier, $principalIdentifier, new DateTimeImmutable());
    }
}
