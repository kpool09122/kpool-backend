<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;
use Source\Wiki\VideoLink\Application\UseCase\Query\PopularVideoReadModel;

class PopularVideoReadModelTest extends TestCase
{
    public function testSerializesPlaybackAndWikiInformationWithoutPersonalData(): void
    {
        $video = new PopularVideoReadModel('id', new YouTubeVideoUrl('https://www.youtube.com/watch?v=abcdefghijk'), 'Title', null, 120, 4, 'ja', 'tl-title');
        self::assertSame([
            'videoLinkIdentifier' => 'id', 'url' => 'https://www.youtube.com/watch?v=abcdefghijk',
            'provider' => 'youtube', 'externalVideoId' => 'abcdefghijk', 'title' => 'Title',
            'thumbnailUrl' => null, 'durationSeconds' => 120, 'likeCount' => 4,
            'language' => 'ja', 'slug' => 'tl-title',
        ], $video->toArray());
    }
}
