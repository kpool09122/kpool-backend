<?php

namespace Tests\Wiki\Song\UseCase\Query;

use Businesses\Wiki\Song\UseCase\Query\SongReadModel;
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
        $belongingNames = ['TWICE', 'MISAMO'];
        $lyricist = '블랙아이드필승';
        $composer = 'Sam Lewis';
        $releaseDate = new DateTimeImmutable('2016-10-24');
        $overview = '"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.';
        $musicVideoLink = 'https://example.youtube.com/watch?v=dQw4w9WgXcQ';
        $coverImagePath = 'https://example.com/resources/public/images/image.webp';
        $readModel = new SongReadModel(
            $songId,
            $name,
            $belongingNames,
            $lyricist,
            $composer,
            $releaseDate,
            $overview,
            $musicVideoLink,
            $coverImagePath,
        );
        $this->assertSame($songId, $readModel->songId());
        $this->assertSame($name, $readModel->name());
        $this->assertSame($belongingNames, $readModel->belongingNames());
        $this->assertSame($lyricist, $readModel->lyricist());
        $this->assertSame($composer, $readModel->composer());
        $this->assertSame($releaseDate, $readModel->releaseDate());
        $this->assertSame($overview, $readModel->overview());
        $this->assertSame($musicVideoLink, $readModel->musicVideoLink());
        $this->assertSame($coverImagePath, $readModel->coverImagePath());
        $this->assertSame([
            'song_id' => $songId,
            'name' => $name,
            'belonging_names' => $belongingNames,
            'lyricist' => $lyricist,
            'composer' => $composer,
            'release_date' => $releaseDate->format('Y-m'),
            'overview' => $overview,
            'music_video_link' => $musicVideoLink,
            'cover_image_path' => $coverImagePath,
        ], $readModel->toArray());
    }
}
