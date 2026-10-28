<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\Service;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Language;

final readonly class VideoWatchRequestDigest
{
    /** @param list<array{wikiTitle: string, videoTitle: ?string, thumbnailUrl: ?string, likeCount: int, requestCount: int}> $videos */
    public function __construct(
        public AccountIdentifier $ownerAccountIdentifier,
        public Language $wikiLanguage,
        public string $wikiSlug,
        public array $videos,
        public int $recentRequestCount,
        public int $newRequestCount,
    ) {
    }
}
