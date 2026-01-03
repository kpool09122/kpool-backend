<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DraftSongTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $createDraftSong = $this->createDummyDraftSong();
        $this->assertSame((string)$createDraftSong->songIdentifier, (string)$createDraftSong->song->songIdentifier());
        $this->assertSame((string)$createDraftSong->publishedSongIdentifier, (string)$createDraftSong->song->publishedSongIdentifier());
        $this->assertSame((string)$createDraftSong->translationSetIdentifier, (string)$createDraftSong->song->translationSetIdentifier());
        $this->assertSame((string)$createDraftSong->editorIdentifier, (string)$createDraftSong->song->editorIdentifier());
        $this->assertSame($createDraftSong->language->value, $createDraftSong->song->language()->value);
        $this->assertSame((string)$createDraftSong->name, (string)$createDraftSong->song->name());
        $this->assertSame((string)$createDraftSong->groupIdentifier, (string)$createDraftSong->song->groupIdentifier());
        $this->assertSame((string)$createDraftSong->talentIdentifier, (string)$createDraftSong->song->talentIdentifier());
        $this->assertSame((string)$createDraftSong->lyricist, (string)$createDraftSong->song->lyricist());
        $this->assertSame((string)$createDraftSong->composer, (string)$createDraftSong->song->composer());
        $this->assertSame((string)$createDraftSong->overView, (string)$createDraftSong->song->overView());
        $this->assertSame((string)$createDraftSong->coverImagePath, (string)$createDraftSong->song->coverImagePath());
        $this->assertSame((string)$createDraftSong->musicVideoLink, (string)$createDraftSong->song->musicVideoLink());
        $this->assertSame($createDraftSong->status, $createDraftSong->song->status());
    }

    /**
     * 正常系：公開済みSongIDのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetPublishedSongIdentifier(): void
    {
        $createDraftSong = $this->createDummyDraftSong();

        $this->assertSame((string)$createDraftSong->publishedSongIdentifier, (string)$createDraftSong->song->publishedSongIdentifier());

        $newPublishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $createDraftSong->song->setPublishedSongIdentifier($newPublishedSongIdentifier);
        $this->assertNotSame((string)$createDraftSong->publishedSongIdentifier, (string)$createDraftSong->song->publishedSongIdentifier());
        $this->assertSame((string)$newPublishedSongIdentifier, (string)$createDraftSong->song->publishedSongIdentifier());
    }

    /**
     * 正常系：SongNameのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetName(): void
    {
        $createDraftSong = $this->createDummyDraftSong();

        $this->assertSame((string)$createDraftSong->name, (string)$createDraftSong->song->name());

        $newName = new SongName('I CAN\'T STOP ME');
        $createDraftSong->song->setName($newName);
        $this->assertNotSame((string)$createDraftSong->name, (string)$createDraftSong->song->name());
        $this->assertSame((string)$newName, (string)$createDraftSong->song->name());
    }

    /**
     * 正常系：AgencyIDのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetAgencyIdentifier(): void
    {
        $createDraftSong = $this->createDummyDraftSong();

        $this->assertSame((string)$createDraftSong->agencyIdentifier, (string)$createDraftSong->song->agencyIdentifier());

        $newAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $createDraftSong->song->setAgencyIdentifier($newAgencyIdentifier);
        $this->assertNotSame((string)$createDraftSong->agencyIdentifier, (string)$createDraftSong->song->agencyIdentifier());
        $this->assertSame((string)$newAgencyIdentifier, (string)$createDraftSong->song->agencyIdentifier());
    }

    /**
     * 正常系：GroupIdentifierのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetGroupIdentifier(): void
    {
        $createDraftSong = $this->createDummyDraftSong();

        $this->assertSame((string)$createDraftSong->groupIdentifier, (string)$createDraftSong->song->groupIdentifier());

        $newGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $createDraftSong->song->setGroupIdentifier($newGroupIdentifier);
        $this->assertNotSame((string)$createDraftSong->groupIdentifier, (string)$createDraftSong->song->groupIdentifier());
        $this->assertSame((string)$newGroupIdentifier, (string)$createDraftSong->song->groupIdentifier());
    }

    /**
     * 正常系：TalentIdentifierのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetTalentIdentifier(): void
    {
        $createDraftSong = $this->createDummyDraftSong();

        $this->assertSame((string)$createDraftSong->talentIdentifier, (string)$createDraftSong->song->talentIdentifier());

        $newTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $createDraftSong->song->setTalentIdentifier($newTalentIdentifier);
        $this->assertNotSame((string)$createDraftSong->talentIdentifier, (string)$createDraftSong->song->talentIdentifier());
        $this->assertSame((string)$newTalentIdentifier, (string)$createDraftSong->song->talentIdentifier());
    }

    /**
     * 正常系：Lyricistのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetLyricist(): void
    {
        $createDraftSong = $this->createDummyDraftSong();

        $this->assertSame((string)$createDraftSong->lyricist, (string)$createDraftSong->song->lyricist());

        $newLyricist = new Lyricist('J.Y. Park');
        $createDraftSong->song->setLyricist($newLyricist);
        $this->assertNotSame((string)$createDraftSong->lyricist, (string)$createDraftSong->song->lyricist());
        $this->assertSame((string)$newLyricist, (string)$createDraftSong->song->lyricist());
    }

    /**
     * 正常系：Composerのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetComposer(): void
    {
        $createDraftSong = $this->createDummyDraftSong();

        $this->assertSame((string)$createDraftSong->composer, (string)$createDraftSong->song->composer());

        $newComposer = new Composer('J.Y. Park');
        $createDraftSong->song->setComposer($newComposer);
        $this->assertNotSame((string)$createDraftSong->composer, (string)$createDraftSong->song->composer());
        $this->assertSame((string)$newComposer, (string)$createDraftSong->song->composer());
    }

    /**
     * 正常系：ReleaseDateのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetReleaseDate(): void
    {
        $createDraftSong = $this->createDummyDraftSong();

        $this->assertSame($createDraftSong->releaseDate->value(), $createDraftSong->song->releaseDate()->value());

        $newReleaseDate = new ReleaseDate(new DateTimeImmutable('2020-10-26'));
        $createDraftSong->song->setReleaseDate($newReleaseDate);
        $this->assertNotSame($createDraftSong->releaseDate->value(), $createDraftSong->song->releaseDate()->value());
        $this->assertSame($newReleaseDate->value(), $createDraftSong->song->releaseDate()->value());
    }

    /**
     * 正常系：OverViewのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetOverView(): void
    {
        $createDraftSong = $this->createDummyDraftSong();

        $this->assertSame((string)$createDraftSong->overView, (string)$createDraftSong->song->overView());

        $newOverView = new Overview('"I CAN\'T STOP ME"는 선과 악의 갈림길에 서서 자기 자신을 제어할 수 없게 되는 위험한 마음을 노래한 곡입니다. 80년대 유럽의 일렉트로닉 사운드와 미국의 신스팝을 융합한 레트로한 멜로디가 특징입니다. 선악의 경계에서 갈등하는 내면의 감정을 중독성 강한 사운드와 파워풀한 퍼포먼스로 표현하고 있습니다. 선과 악을 상징하는 흑백 의상을 입은 멤버들이 마주하는 뮤직비디오 또한 인상적이며, 지금까지의 귀여운 이미지와는 선을 긋는 한층 더 성숙한 트와이스의 모습을 보여준 곡입니다.');
        $createDraftSong->song->setOverView($newOverView);
        $this->assertNotSame((string)$createDraftSong->overView, (string)$createDraftSong->song->overView());
        $this->assertSame((string)$newOverView, (string)$createDraftSong->song->overView());
    }

    /**
     * 正常系：CoverImagePathのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetImageLink(): void
    {
        $createDraftSong = $this->createDummyDraftSong();

        $this->assertSame((string)$createDraftSong->coverImagePath, (string)$createDraftSong->song->coverImagePath());

        $newCoverImagePath = new ImagePath('/resources/public/images/after.webp');

        $createDraftSong->song->setCoverImagePath($newCoverImagePath);
        $this->assertNotSame((string)$createDraftSong->coverImagePath, (string)$createDraftSong->song->coverImagePath());
        $this->assertSame((string)$newCoverImagePath, (string)$createDraftSong->song->coverImagePath());
    }

    /**
     * 正常系：MusicVideoLinkのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetMusicVideoLink(): void
    {
        $createDraftSong = $this->createDummyDraftSong();

        $this->assertSame((string)$createDraftSong->musicVideoLink, (string)$createDraftSong->song->musicVideoLink());

        $newMusicVideoLink = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');

        $createDraftSong->song->setMusicVideoLink($newMusicVideoLink);
        $this->assertNotSame((string)$createDraftSong->musicVideoLink, (string)$createDraftSong->song->musicVideoLink());
        $this->assertSame((string)$newMusicVideoLink, (string)$createDraftSong->song->musicVideoLink());
    }

    /**
     * 正常系：Statusのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetStatus(): void
    {
        $createDraftSong = $this->createDummyDraftSong();

        $this->assertSame($createDraftSong->status, $createDraftSong->song->status());

        $newStatus = ApprovalStatus::Approved;

        $createDraftSong->song->setStatus($newStatus);
        $this->assertNotSame($createDraftSong->status, $createDraftSong->song->status());
        $this->assertSame($newStatus, $createDraftSong->song->status());
    }

    /**
     * 正常系：MergerIdentifierのsetter/getterが正しく動作すること.
     *
     * @return void
     */
    public function testSetMergerIdentifier(): void
    {
        $createDraftSong = $this->createDummyDraftSong();
        $song = $createDraftSong->song;

        $this->assertNull($song->mergerIdentifier());

        $mergerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $song->setMergerIdentifier($mergerIdentifier);
        $this->assertSame((string)$mergerIdentifier, (string)$song->mergerIdentifier());
    }

    /**
     * 正常系：MergedAtのsetter/getterが正しく動作すること.
     *
     * @return void
     */
    public function testSetMergedAt(): void
    {
        $createDraftSong = $this->createDummyDraftSong();
        $song = $createDraftSong->song;

        $this->assertNull($song->mergedAt());

        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');
        $song->setMergedAt($mergedAt);
        $this->assertSame($mergedAt, $song->mergedAt());
    }

    /**
     * ダミーのDraftSongを作成するヘルパーメソッド
     *
     * @return DraftSongTestData
     */
    private function createDummyDraftSong(): DraftSongTestData
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new SongName('TT');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.');
        $coverImagePath = new ImagePath('/resources/public/images/test.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $status = ApprovalStatus::Pending;

        $song = new DraftSong(
            $songIdentifier,
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $agencyIdentifier,
            $groupIdentifier,
            $talentIdentifier,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            $status,
        );

        return new DraftSongTestData(
            songIdentifier: $songIdentifier,
            publishedSongIdentifier: $publishedSongIdentifier,
            translationSetIdentifier: $translationSetIdentifier,
            editorIdentifier: $editorIdentifier,
            language: $language,
            name: $name,
            agencyIdentifier: $agencyIdentifier,
            groupIdentifier: $groupIdentifier,
            talentIdentifier: $talentIdentifier,
            lyricist: $lyricist,
            composer: $composer,
            releaseDate: $releaseDate,
            overView: $overView,
            coverImagePath: $coverImagePath,
            musicVideoLink: $musicVideoLink,
            status: $status,
            song: $song,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class DraftSongTestData
{
    public function __construct(
        public SongIdentifier           $songIdentifier,
        public SongIdentifier           $publishedSongIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public PrincipalIdentifier      $editorIdentifier,
        public Language                 $language,
        public SongName                 $name,
        public AgencyIdentifier         $agencyIdentifier,
        public GroupIdentifier          $groupIdentifier,
        public TalentIdentifier         $talentIdentifier,
        public Lyricist                 $lyricist,
        public Composer                 $composer,
        public ReleaseDate              $releaseDate,
        public Overview                 $overView,
        public ImagePath                $coverImagePath,
        public ExternalContentLink      $musicVideoLink,
        public ApprovalStatus           $status,
        public DraftSong                $song,
    ) {
    }
}
