<?php

namespace Tests\Member\UseCase\Query;

use Businesses\Member\UseCase\Query\SongReadModel;
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
        $releaseDate = new DateTimeImmutable('2020-01-01');
        $youtubeLink = 'https://example.youtube.com/watch?v=dQw4w9WgXcQ';
        $imageUrl = 'https://example.com/resources/public/images/image.webp';
        $readModel = new SongReadModel(
            $songId,
            $name,
            $releaseDate,
            $youtubeLink,
            $imageUrl,
        );
        $this->assertSame($songId, $readModel->songId());
        $this->assertSame($name, $readModel->name());
        $this->assertSame($releaseDate, $readModel->releaseDate());
        $this->assertSame($youtubeLink, $readModel->youtubeLink());
        $this->assertSame($imageUrl, $readModel->imageUrl());
        $this->assertSame([
            'song_id' => $songId,
            'name' => $name,
            'release_date' => $releaseDate->format('Y-m'),
            'youtube_link' => $youtubeLink,
            'image_url' => $imageUrl,
        ], $readModel->toArray());
    }
}
