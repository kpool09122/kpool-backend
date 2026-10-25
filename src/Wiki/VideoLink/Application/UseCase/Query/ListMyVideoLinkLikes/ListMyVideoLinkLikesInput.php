<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes;

use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;

readonly class ListMyVideoLinkLikesInput implements ListMyVideoLinkLikesInputPort
{
    public const int MAX_VIDEO_LINKS = 100;

    /**
     * @param list<VideoLinkIdentifier>|null $videoLinkIdentifiers
     * @throws InvalidArgumentException
     */
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private ?array $videoLinkIdentifiers = null,
        private int $page = 1,
        private int $perPage = 20,
    ) {
        if ($videoLinkIdentifiers !== null && (count($videoLinkIdentifiers) < 1 || count($videoLinkIdentifiers) > self::MAX_VIDEO_LINKS)) {
            throw new InvalidArgumentException('Between 1 and 100 video identifiers are required.');
        }
        if ($page < 1 || $perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Page must be positive and perPage must be between 1 and 100.');
        }
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    /** @return list<VideoLinkIdentifier>|null */
    public function videoLinkIdentifiers(): ?array
    {
        return $this->videoLinkIdentifiers;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }
}
