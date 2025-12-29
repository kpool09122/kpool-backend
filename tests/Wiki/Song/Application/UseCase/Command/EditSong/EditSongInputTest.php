<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\EditSong;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Application\UseCase\Command\EditSong\EditSongInput;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\BelongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class EditSongInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $name = new SongName('TT');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $belongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.');
        $base64EncodedCoverImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());

        $input = new EditSongInput(
            $songIdentifier,
            $name,
            $agencyIdentifier,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $base64EncodedCoverImage,
            $musicVideoLink,
            $principalIdentifier,
        );
        $this->assertSame((string)$songIdentifier, (string)$input->songIdentifier());
        $this->assertSame((string)$name, (string)$input->name());
        $this->assertSame((string)$agencyIdentifier, (string)$input->agencyIdentifier());
        $this->assertSame($belongIdentifiers, $input->belongIdentifiers());
        $this->assertSame((string)$lyricist, (string)$input->lyricist());
        $this->assertSame((string)$composer, (string)$input->composer());
        $this->assertSame($releaseDate->value(), $input->releaseDate()->value());
        $this->assertSame((string)$overView, (string)$input->overView());
        $this->assertSame($base64EncodedCoverImage, $input->base64EncodedCoverImage());
        $this->assertSame($musicVideoLink, $input->musicVideoLink());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}
