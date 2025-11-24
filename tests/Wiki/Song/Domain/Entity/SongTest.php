<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\Entity\Song;
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

class SongTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $createSong = $this->createDummySong();
        $this->assertSame((string)$createSong->songIdentifier, (string)$createSong->song->songIdentifier());
        $this->assertSame((string)$createSong->translationSetIdentifier, (string)$createSong->song->translationSetIdentifier());
        $this->assertSame($createSong->language->value, $createSong->song->language()->value);
        $this->assertSame((string)$createSong->name, (string)$createSong->song->name());
        $this->assertSame((string)$createSong->agencyIdentifier, (string)$createSong->song->agencyIdentifier());
        $this->assertSame($createSong->belongIdentifiers, $createSong->song->belongIdentifiers());
        $this->assertSame((string)$createSong->lyricist, (string)$createSong->song->lyricist());
        $this->assertSame((string)$createSong->composer, (string)$createSong->song->composer());
        $this->assertSame((string)$createSong->overView, (string)$createSong->song->overView());
        $this->assertSame((string)$createSong->coverImagePath, (string)$createSong->song->coverImagePath());
        $this->assertSame((string)$createSong->musicVideoLink, (string)$createSong->song->musicVideoLink());
    }

    /**
     * 正常系：SongNameのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetName(): void
    {
        $createSong = $this->createDummySong();

        $this->assertSame((string)$createSong->name, (string)$createSong->song->name());

        $newName = new SongName('I CAN\'T STOP ME');
        $createSong->song->setName($newName);
        $this->assertNotSame((string)$createSong->name, (string)$createSong->song->name());
        $this->assertSame((string)$newName, (string)$createSong->song->name());
    }

    /**
     * 正常系：BelongIdentifiersのsetterが正しく動作すること(null許容).
     *
     * @return void
     */
    public function testSetBelongIdentifiers(): void
    {
        $createSong = $this->createDummySong();

        $this->assertSame($createSong->belongIdentifiers, $createSong->song->belongIdentifiers());

        $newBelongIdentifier = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $createSong->song->setBelongIdentifiers($newBelongIdentifier);
        $this->assertNotSame($createSong->belongIdentifiers, $createSong->song->belongIdentifiers());
        $this->assertSame($newBelongIdentifier, $createSong->song->belongIdentifiers());

        $createSong->song->setBelongIdentifiers([]);
        $this->assertEmpty($createSong->song->belongIdentifiers());
    }

    /**
     * 正常系：Lyricistのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetLyricist(): void
    {
        $createSong = $this->createDummySong();

        $this->assertSame((string)$createSong->lyricist, (string)$createSong->song->lyricist());

        $newLyricist = new Lyricist('J.Y. Park');
        $createSong->song->setLyricist($newLyricist);
        $this->assertNotSame((string)$createSong->lyricist, (string)$createSong->song->lyricist());
        $this->assertSame((string)$newLyricist, (string)$createSong->song->lyricist());
    }

    /**
     * 正常系：Composerのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetComposer(): void
    {
        $createSong = $this->createDummySong();

        $this->assertSame((string)$createSong->composer, (string)$createSong->song->composer());

        $newComposer = new Composer('J.Y. Park');
        $createSong->song->setComposer($newComposer);
        $this->assertNotSame((string)$createSong->composer, (string)$createSong->song->composer());
        $this->assertSame((string)$newComposer, (string)$createSong->song->composer());
    }

    /**
     * 正常系：ReleaseDateのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetReleaseDate(): void
    {
        $createSong = $this->createDummySong();

        $this->assertSame($createSong->releaseDate->value(), $createSong->song->releaseDate()->value());

        $newReleaseDate = new ReleaseDate(new DateTimeImmutable('2020-10-26'));
        $createSong->song->setReleaseDate($newReleaseDate);
        $this->assertNotSame($createSong->releaseDate->value(), $createSong->song->releaseDate()->value());
        $this->assertSame($newReleaseDate->value(), $createSong->song->releaseDate()->value());
    }

    /**
     * 正常系：OverViewのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetOverView(): void
    {
        $createSong = $this->createDummySong();

        $this->assertSame((string)$createSong->overView, (string)$createSong->song->overView());

        $newOverView = new Overview('"I CAN\'T STOP ME"는 선과 악의 갈림길에 서서 자기 자신을 제어할 수 없게 되는 위험한 마음을 노래한 곡입니다. 80년대 유럽의 일렉트로닉 사운드와 미국의 신스팝을 융합한 레트로한 멜로디가 특징입니다. 선악의 경계에서 갈등하는 내면의 감정을 중독성 강한 사운드와 파워풀한 퍼포먼스로 표현하고 있습니다. 선과 악을 상징하는 흑백 의상을 입은 멤버들이 마주하는 뮤직비디오 또한 인상적이며, 지금까지의 귀여운 이미지와는 선을 긋는 한층 더 성숙한 트와이스의 모습을 보여준 곡입니다.');
        $createSong->song->setOverView($newOverView);
        $this->assertNotSame((string)$createSong->overView, (string)$createSong->song->overView());
        $this->assertSame((string)$newOverView, (string)$createSong->song->overView());
    }

    /**
     * 正常系：CoverImagePathのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetImageLink(): void
    {
        $createSong = $this->createDummySong();

        $this->assertSame((string)$createSong->coverImagePath, (string)$createSong->song->coverImagePath());

        $newCoverImagePath = new ImagePath('/resources/public/images/after.webp');

        $createSong->song->setCoverImagePath($newCoverImagePath);
        $this->assertNotSame((string)$createSong->coverImagePath, (string)$createSong->song->coverImagePath());
        $this->assertSame((string)$newCoverImagePath, (string)$createSong->song->coverImagePath());
    }

    /**
     * 正常系：MusicVideoLinkのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetMusicVideoLink(): void
    {
        $createSong = $this->createDummySong();

        $this->assertSame((string)$createSong->musicVideoLink, (string)$createSong->song->musicVideoLink());

        $newMusicVideoLink = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');

        $createSong->song->setMusicVideoLink($newMusicVideoLink);
        $this->assertNotSame((string)$createSong->musicVideoLink, (string)$createSong->song->musicVideoLink());
        $this->assertSame((string)$newMusicVideoLink, (string)$createSong->song->musicVideoLink());
    }

    /**
     * 正常系：updateVersionが正しく動作すること.
     *
     * @return void
     */
    public function testUpdateVersion(): void
    {
        $createSong = $this->createDummySong();
        $song = $createSong->song;

        $this->assertSame($createSong->version->value(), $song->version()->value());

        $song->updateVersion();

        $this->assertNotSame($createSong->version->value(), $song->version()->value());
        $this->assertSame($createSong->version->value() + 1, $song->version()->value());
    }

    /**
     * ダミーのSongを作成するヘルパーメソッド
     *
     * @return SongTestData
     */
    private function createDummySong(): SongTestData
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $language = Language::KOREAN;
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
        $coverImagePath = new ImagePath('/resources/public/images/test.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $version = new Version(1);

        $song = new Song(
            $songIdentifier,
            $translationSetIdentifier,
            $language,
            $name,
            $agencyIdentifier,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            $version
        );

        return new SongTestData(
            songIdentifier: $songIdentifier,
            translationSetIdentifier: $translationSetIdentifier,
            language: $language,
            name: $name,
            agencyIdentifier: $agencyIdentifier,
            belongIdentifiers: $belongIdentifiers,
            lyricist: $lyricist,
            composer: $composer,
            releaseDate: $releaseDate,
            overView: $overView,
            coverImagePath: $coverImagePath,
            musicVideoLink: $musicVideoLink,
            song: $song,
            version: $version
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class SongTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     * @param BelongIdentifier[] $belongIdentifiers
     */
    public function __construct(
        public SongIdentifier           $songIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public Language                 $language,
        public SongName                 $name,
        public AgencyIdentifier         $agencyIdentifier,
        public array                    $belongIdentifiers,
        public Lyricist                 $lyricist,
        public Composer                 $composer,
        public ReleaseDate              $releaseDate,
        public Overview                 $overView,
        public ImagePath                $coverImagePath,
        public ExternalContentLink      $musicVideoLink,
        public Song                     $song,
        public Version $version
    ) {
    }
}
