<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Query;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\VideoLink\Application\UseCase\Query\LikedVideoLinkReadModel;

class LikedVideoLinkReadModelTest extends TestCase
{
    /** @return array<string, array{?string, ?int}> */
    public static function metadata(): array
    {
        return ['unknown' => [null, null], 'known' => ['https://img.youtube.com/vi/abcdefghijk/default.jpg', 120], 'zero duration' => [null, 0]];
    }

    #[DataProvider('metadata')]
    public function testSerializesVideoAndSourceWikiMetadata(?string $thumbnail, ?int $duration): void
    {
        $video = new LikedVideoLinkReadModel('id', 'https://www.youtube.com/watch?v=abcdefghijk', 'Title', $thumbnail, $duration, 'en', 'song', 'sg-title', '2026-01-01T00:00:00+00:00');
        self::assertSame([
            'videoLinkIdentifier' => 'id', 'url' => 'https://www.youtube.com/watch?v=abcdefghijk', 'title' => 'Title', 'thumbnailUrl' => $thumbnail, 'durationSeconds' => $duration,
            'language' => 'en', 'resourceType' => 'song', 'slug' => 'sg-title', 'likedAt' => '2026-01-01T00:00:00+00:00',
        ], $video->toArray());
    }
}
