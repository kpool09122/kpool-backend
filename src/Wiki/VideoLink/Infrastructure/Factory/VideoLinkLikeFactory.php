<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\Entity\VideoLinkLike;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkLikeFactoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkLikeIdentifier;

readonly class VideoLinkLikeFactory implements VideoLinkLikeFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    public function create(VideoLinkIdentifier $videoLinkIdentifier, PrincipalIdentifier $principalIdentifier): VideoLinkLike
    {
        return new VideoLinkLike(new VideoLinkLikeIdentifier($this->uuidGenerator->generate()), $videoLinkIdentifier, $principalIdentifier, new DateTimeImmutable());
    }
}
