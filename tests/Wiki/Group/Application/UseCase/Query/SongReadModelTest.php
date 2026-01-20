<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Query;

use DateTimeImmutable;
use Source\Wiki\Group\Application\UseCase\Query\SongReadModel;
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
        $songId = StrTestHelper::generateUuid();
        $name = 'TT';
        $releaseDate = new DateTimeImmutable('2016-10-24');
        $coverImagePath = 'https://example.com/resources/public/images/image.webp';
        $readModel = new SongReadModel(
            $songId,
            $name,
            $releaseDate,
            $coverImagePath,
        );
        $this->assertSame($songId, $readModel->songId());
        $this->assertSame($name, $readModel->name());
        $this->assertSame($releaseDate, $readModel->releaseDate());
        $this->assertSame($coverImagePath, $readModel->coverImagePath());
        $this->assertSame([
            'song_id' => $songId,
            'name' => $name,
            'release_date' => $releaseDate->format('Y-m'),
            'cover_image_path' => $coverImagePath,
        ], $readModel->toArray());
    }
}
