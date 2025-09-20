<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Domain\Entity;

use Businesses\Shared\ValueObject\ExternalContentLink;
use Businesses\Shared\ValueObject\ImagePath;
use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Song\Domain\Entity\Song;
use Businesses\Wiki\Song\Domain\ValueObject\BelongIdentifier;
use Businesses\Wiki\Song\Domain\ValueObject\Composer;
use Businesses\Wiki\Song\Domain\ValueObject\Lyricist;
use Businesses\Wiki\Song\Domain\ValueObject\Overview;
use Businesses\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Businesses\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Businesses\Wiki\Song\Domain\ValueObject\SongName;
use DateTimeImmutable;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SongTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $belongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.');
        $coverImagePath = new ImagePath('/resources/public/images/test.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $song = new Song(
            $songIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
        );
        $this->assertSame((string)$songIdentifier, (string)$song->songIdentifier());
        $this->assertSame($translation->value, $song->translation()->value);
        $this->assertSame((string)$name, (string)$song->name());
        $this->assertSame($belongIdentifiers, $song->belongIdentifiers());
        $this->assertSame((string)$lyricist, (string)$song->lyricist());
        $this->assertSame((string)$composer, (string)$song->composer());
        $this->assertSame((string)$overView, (string)$song->overView());
        $this->assertSame((string)$coverImagePath, (string)$song->coverImagePath());
        $this->assertSame((string)$musicVideoLink, (string)$song->musicVideoLink());
    }

    /**
     * 正常系：MemberNameのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetName(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $belongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.');
        $coverImagePath = new ImagePath('/resources/public/images/test.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $song = new Song(
            $songIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
        );
        $this->assertSame((string)$name, (string)$song->name());

        $newName = new SongName('I CAN\'T STOP ME');
        $song->setName($newName);
        $this->assertNotSame((string)$name, (string)$song->name());
        $this->assertSame((string)$newName, (string)$song->name());
    }

    /**
     * 正常系：BelongIdentifiersのsetterが正しく動作すること(null許容).
     *
     * @return void
     */
    public function testSetBelongIdentifiers(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $belongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.');
        $coverImagePath = new ImagePath('/resources/public/images/test.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $song = new Song(
            $songIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
        );
        $this->assertSame($belongIdentifiers, $song->belongIdentifiers());

        $newBelongIdentifier = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $song->setBelongIdentifiers($newBelongIdentifier);
        $this->assertNotSame($belongIdentifiers, $song->belongIdentifiers());
        $this->assertSame($newBelongIdentifier, $song->belongIdentifiers());

        $song->setBelongIdentifiers([]);
        $this->assertEmpty($song->belongIdentifiers());
    }

    /**
     * 正常系：Lyricistのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetLyricist(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $belongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.');
        $coverImagePath = new ImagePath('/resources/public/images/test.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $song = new Song(
            $songIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
        );
        $this->assertSame((string)$lyricist, (string)$song->lyricist());

        $newLyricist = new Lyricist('J.Y. Park');
        $song->setLyricist($newLyricist);
        $this->assertNotSame((string)$lyricist, (string)$song->lyricist());
        $this->assertSame((string)$newLyricist, (string)$song->lyricist());
    }

    /**
     * 正常系：Composerのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetComposer(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $belongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.');
        $coverImagePath = new ImagePath('/resources/public/images/test.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $song = new Song(
            $songIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
        );
        $this->assertSame((string)$composer, (string)$song->composer());

        $newComposer = new Composer('J.Y. Park');
        $song->setComposer($newComposer);
        $this->assertNotSame((string)$composer, (string)$song->composer());
        $this->assertSame((string)$newComposer, (string)$song->composer());
    }

    /**
     * 正常系：ReleaseDateのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetReleaseDate(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $belongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.');
        $coverImagePath = new ImagePath('/resources/public/images/test.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $song = new Song(
            $songIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
        );
        $this->assertSame($releaseDate->value(), $song->releaseDate()->value());

        $newReleaseDate = new ReleaseDate(new DateTimeImmutable('2020-10-26'));
        $song->setReleaseDate($newReleaseDate);
        $this->assertNotSame($releaseDate->value(), $song->releaseDate()->value());
        $this->assertSame($newReleaseDate->value(), $song->releaseDate()->value());
    }

    /**
     * 正常系：OverViewのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetOverView(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $belongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.');
        $coverImagePath = new ImagePath('/resources/public/images/test.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $song = new Song(
            $songIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
        );
        $this->assertSame((string)$overView, (string)$song->overView());

        $newOverView = new Overview('"I CAN\'T STOP ME"는 선과 악의 갈림길에 서서 자기 자신을 제어할 수 없게 되는 위험한 마음을 노래한 곡입니다. 80년대 유럽의 일렉트로닉 사운드와 미국의 신스팝을 융합한 레트로한 멜로디가 특징입니다. 선악의 경계에서 갈등하는 내면의 감정을 중독성 강한 사운드와 파워풀한 퍼포먼스로 표현하고 있습니다. 선과 악을 상징하는 흑백 의상을 입은 멤버들이 마주하는 뮤직비디오 또한 인상적이며, 지금까지의 귀여운 이미지와는 선을 긋는 한층 더 성숙한 트와이스의 모습을 보여준 곡입니다.');
        $song->setOverView($newOverView);
        $this->assertNotSame((string)$overView, (string)$song->overView());
        $this->assertSame((string)$newOverView, (string)$song->overView());
    }

    /**
     * 正常系：CoverImagePathのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetImageLink(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $belongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.');
        $coverImagePath = new ImagePath('/resources/public/images/test.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $song = new Song(
            $songIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
        );
        $this->assertSame((string)$coverImagePath, (string)$song->coverImagePath());

        $newCoverImagePath = new ImagePath('/resources/public/images/after.webp');

        $song->setCoverImagePath($newCoverImagePath);
        $this->assertNotSame((string)$coverImagePath, (string)$song->coverImagePath());
        $this->assertSame((string)$newCoverImagePath, (string)$song->coverImagePath());
    }

    /**
     * 正常系：MusicVideoLinkのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetMusicVideoLink(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $belongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.');
        $coverImagePath = new ImagePath('/resources/public/images/test.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $song = new Song(
            $songIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
        );
        $this->assertSame((string)$musicVideoLink, (string)$song->musicVideoLink());

        $newMusicVideoLink = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');

        $song->setMusicVideoLink($newMusicVideoLink);
        $this->assertNotSame((string)$musicVideoLink, (string)$song->musicVideoLink());
        $this->assertSame((string)$newMusicVideoLink, (string)$song->musicVideoLink());
    }
}
