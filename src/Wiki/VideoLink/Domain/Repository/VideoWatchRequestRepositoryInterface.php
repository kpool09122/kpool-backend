<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Repository;

use DateTimeImmutable;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\Entity\VideoWatchRequest;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface VideoWatchRequestRepositoryInterface
{
    public function save(VideoWatchRequest $videoWatchRequest): void;

    /** @return list<VideoWatchRequest> */
    public function findByWikiAndPrincipal(WikiIdentifier $wikiIdentifier, PrincipalIdentifier $principalIdentifier, DateTimeImmutable $after): array;

    public function findLatestByVideoLink(VideoLinkIdentifier $videoLinkIdentifier): ?VideoWatchRequest;

    public function findLatestByVideoLinkAndPrincipal(VideoLinkIdentifier $videoLinkIdentifier, PrincipalIdentifier $principalIdentifier): ?VideoWatchRequest;
}
