<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes;

use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;

readonly class ListMyVideoLinkLikesInput implements ListMyVideoLinkLikesInputPort
{
    public const int MAX_VIDEO_LINKS = 100;

    /**
     * @param list<VideoLinkIdentifier> $videoLinkIdentifiers
     * @throws InvalidArgumentException
     */
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private array $videoLinkIdentifiers,
    ) {
        if (count($videoLinkIdentifiers) < 1 || count($videoLinkIdentifiers) > self::MAX_VIDEO_LINKS) {
            throw new InvalidArgumentException('Between 1 and 100 video identifiers are required.');
        }
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    /** @return list<VideoLinkIdentifier> */
    public function videoLinkIdentifiers(): array
    {
        return $this->videoLinkIdentifiers;
    }
}
