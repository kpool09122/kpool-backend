<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Application\UseCase\Query\PlaylistDetailReadModel;
use Source\Wiki\Playlist\Application\UseCase\Query\PlaylistItemReadModel;

class PlaylistDetailReadModelTest extends TestCase
{
    public function testSerializesMetadataAndCountsStoredItems(): void
    {
        $items = [new PlaylistItemReadModel(null, 'youtube', 'abcdefghijk', 0, 'https://example.com/first', 'First', null, null), new PlaylistItemReadModel('source', 'youtube', '01234567890', 1, 'https://example.com/second', 'Second', 'https://example.com/image', 60)];
        $model = new PlaylistDetailReadModel('playlist', 'Name', 'created', 'updated', $items);
        self::assertSame([
            'playlistIdentifier' => 'playlist',
            'name' => 'Name',
            'videoCount' => 2,
            'createdAt' => 'created',
            'updatedAt' => 'updated',
            'items' => [$items[0]->toArray(), $items[1]->toArray()],
        ], $model->toArray());
        self::assertSame(['playlistIdentifier' => 'empty', 'name' => 'Empty', 'videoCount' => 0, 'createdAt' => 'created', 'updatedAt' => 'updated', 'items' => []], (new PlaylistDetailReadModel('empty', 'Empty', 'created', 'updated', []))->toArray());
    }
}
