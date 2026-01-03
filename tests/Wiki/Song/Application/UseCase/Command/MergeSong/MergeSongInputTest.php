<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\MergeSong;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Song\Application\UseCase\Command\MergeSong\MergeSongInput;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class MergeSongInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $name = new SongName('TT');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다.');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $input = new MergeSongInput(
            $songIdentifier,
            $name,
            $agencyIdentifier,
            $groupIdentifier,
            $talentIdentifier,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $musicVideoLink,
            $principalIdentifier,
            $mergedAt,
        );
        $this->assertSame((string)$songIdentifier, (string)$input->songIdentifier());
        $this->assertSame((string)$name, (string)$input->name());
        $this->assertSame((string)$agencyIdentifier, (string)$input->agencyIdentifier());
        $this->assertSame($groupIdentifier, $input->groupIdentifier());
        $this->assertSame($talentIdentifier, $input->talentIdentifier());
        $this->assertSame((string)$lyricist, (string)$input->lyricist());
        $this->assertSame((string)$composer, (string)$input->composer());
        $this->assertSame($releaseDate->value(), $input->releaseDate()->value());
        $this->assertSame((string)$overView, (string)$input->overView());
        $this->assertSame($musicVideoLink, $input->musicVideoLink());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($mergedAt, $input->mergedAt());
    }

    /**
     * 正常系: nullable値がnullでもインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithNullValues(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $name = new SongName('TT');
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다.');
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $input = new MergeSongInput(
            $songIdentifier,
            $name,
            null,
            null,
            null,
            $lyricist,
            $composer,
            null,
            $overView,
            null,
            $principalIdentifier,
            $mergedAt,
        );
        $this->assertSame((string)$songIdentifier, (string)$input->songIdentifier());
        $this->assertSame((string)$name, (string)$input->name());
        $this->assertNull($input->agencyIdentifier());
        $this->assertNull($input->groupIdentifier());
        $this->assertNull($input->talentIdentifier());
        $this->assertSame((string)$lyricist, (string)$input->lyricist());
        $this->assertSame((string)$composer, (string)$input->composer());
        $this->assertNull($input->releaseDate());
        $this->assertSame((string)$overView, (string)$input->overView());
        $this->assertNull($input->musicVideoLink());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($mergedAt, $input->mergedAt());
    }
}
