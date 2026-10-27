<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;
use Source\Wiki\VideoLink\Application\UseCase\Query\LikedVideoLinkReadModel;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesOutput;

class ListMyVideoLinkLikesOutputTest extends TestCase
{
    public function testSerializesEmptyListAndReplacesPreviousResults(): void
    {
        $output = new ListMyVideoLinkLikesOutput();
        $empty = ['likedVideoLinkIdentifiers' => [], 'videos' => [], 'pagination' => null];
        self::assertSame($empty, $output->toArray());
        $video = new LikedVideoLinkReadModel('id1', new YouTubeVideoUrl('https://www.youtube.com/watch?v=abcdefghijk'), 'Title', null, null, 'ja', 'talent', 'tl-example', '2026-01-01T00:00:00+00:00');
        $pagination = ['currentPage' => 1, 'perPage' => 20, 'total' => 1];
        $output->output(['id1'], [$video], $pagination);
        self::assertSame([
            'likedVideoLinkIdentifiers' => ['id1'],
            'videos' => [[
                'provider' => 'youtube', 'externalVideoId' => 'abcdefghijk', 'videoLinkIdentifier' => 'id1', 'url' => 'https://www.youtube.com/watch?v=abcdefghijk', 'title' => 'Title',
                'thumbnailUrl' => null, 'durationSeconds' => null, 'language' => 'ja', 'resourceType' => 'talent', 'slug' => 'tl-example', 'likedAt' => '2026-01-01T00:00:00+00:00', 'playlists' => [],
            ]],
            'pagination' => $pagination,
        ], $output->toArray());
        $output->output([], []);
        self::assertSame($empty, $output->toArray());
        $output->output([], [], ['currentPage' => 1, 'perPage' => 20, 'total' => 0]);
        self::assertSame(['likedVideoLinkIdentifiers' => [], 'videos' => [], 'pagination' => ['currentPage' => 1, 'perPage' => 20, 'total' => 0]], $output->toArray());
    }
}
