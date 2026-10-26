<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Application\UseCase\Query;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Application\UseCase\Query\PlaylistItemReadModel;

class PlaylistItemReadModelTest extends TestCase
{
    /** @return iterable<string, array{?string, ?string, ?int}> */
    public static function metadata(): iterable
    {
        yield 'populated' => ['source', 'https://example.com/image.jpg', 120];
        yield 'nullable' => [null, null, null];
        yield 'zero duration' => ['source', null, 0];
    }

    #[DataProvider('metadata')]
    public function testSerializesSnapshot(?string $source, ?string $thumbnail, ?int $duration): void
    {
        $model = new PlaylistItemReadModel('item', $source, 'youtube', 'abcdefghijk', 49, 'https://www.youtube.com/watch?v=abcdefghijk', 'Title', $thumbnail, $duration);
        self::assertSame([
            'playlistItemIdentifier' => 'item',
            'sourceVideoLinkIdentifier' => $source,
            'provider' => 'youtube',
            'externalVideoId' => 'abcdefghijk',
            'position' => 49,
            'url' => 'https://www.youtube.com/watch?v=abcdefghijk',
            'title' => 'Title',
            'thumbnailUrl' => $thumbnail,
            'durationSeconds' => $duration,
        ], $model->toArray());
    }
}
