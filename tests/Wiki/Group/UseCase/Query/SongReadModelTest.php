<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\UseCase\Query;

use Businesses\Wiki\Group\UseCase\Query\SongReadModel;
use DateTimeImmutable;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SongReadModelTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $songId = StrTestHelper::generateUlid();
        $name = 'TT';
        $releaseDate = new DateTimeImmutable('2016-10-24');
        $musicVideoLink = 'https://example.youtube.com/watch?v=dQw4w9WgXcQ';
        $coverImagePath = 'https://example.com/resources/public/images/image.webp';
        $readModel = new SongReadModel(
            $songId,
            $name,
            $releaseDate,
            $musicVideoLink,
            $coverImagePath,
        );
        $this->assertSame($songId, $readModel->songId());
        $this->assertSame($name, $readModel->name());
        $this->assertSame($releaseDate, $readModel->releaseDate());
        $this->assertSame($musicVideoLink, $readModel->musicVideoLink());
        $this->assertSame($coverImagePath, $readModel->coverImagePath());
        $this->assertSame([
            'song_id' => $songId,
            'name' => $name,
            'release_date' => $releaseDate->format('Y-m'),
            'music_video_link' => $musicVideoLink,
            'cover_image_path' => $coverImagePath,
        ], $readModel->toArray());
    }
}
