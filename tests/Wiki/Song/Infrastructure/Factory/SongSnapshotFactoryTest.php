<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Infrastructure\Factory;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Factory\SongSnapshotFactoryInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Song\Infrastructure\Factory\SongSnapshotFactory;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SongSnapshotFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(SongSnapshotFactoryInterface::class);
        $this->assertInstanceOf(SongSnapshotFactory::class, $factory);
    }

    /**
     * 正常系: SongSnapshot Entityが正しく作成されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new SongName('TT');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('TT is a song by TWICE.');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $version = new Version(3);

        $song = new Song(
            $songIdentifier,
            $translationSetIdentifier,
            $language,
            $name,
            $agencyIdentifier,
            $groupIdentifier,
            $talentIdentifier,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $musicVideoLink,
            $version,
        );

        $factory = $this->app->make(SongSnapshotFactoryInterface::class);
        $snapshot = $factory->create($song);

        $this->assertTrue(UuidValidator::isValid((string)$snapshot->snapshotIdentifier()));
        $this->assertSame((string)$songIdentifier, (string)$snapshot->songIdentifier());
        $this->assertSame((string)$translationSetIdentifier, (string)$snapshot->translationSetIdentifier());
        $this->assertSame($language->value, $snapshot->language()->value);
        $this->assertSame((string)$name, (string)$snapshot->name());
        $this->assertSame((string)$agencyIdentifier, (string)$snapshot->agencyIdentifier());
        $this->assertSame($groupIdentifier, $snapshot->groupIdentifier());
        $this->assertSame($talentIdentifier, $snapshot->talentIdentifier());
        $this->assertSame((string)$lyricist, (string)$snapshot->lyricist());
        $this->assertSame((string)$composer, (string)$snapshot->composer());
        $this->assertSame($releaseDate->value(), $snapshot->releaseDate()->value());
        $this->assertSame((string)$overView, (string)$snapshot->overView());
        $this->assertSame((string)$musicVideoLink, (string)$snapshot->musicVideoLink());
        $this->assertSame($version->value(), $snapshot->version()->value());
        $this->assertInstanceOf(DateTimeImmutable::class, $snapshot->createdAt());
    }

    /**
     * 正常系: agencyIdentifierがnullのSongからSnapshotが作成されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithNullAgencyIdentifier(): void
    {
        $song = new Song(
            new SongIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new SongName('TT'),
            null,
            null,
            null,
            new Lyricist(''),
            new Composer(''),
            null,
            new Overview(''),
            null,
            new Version(1),
        );

        $factory = $this->app->make(SongSnapshotFactoryInterface::class);
        $snapshot = $factory->create($song);

        $this->assertNull($snapshot->agencyIdentifier());
        $this->assertNull($snapshot->groupIdentifier());
        $this->assertNull($snapshot->talentIdentifier());
        $this->assertNull($snapshot->releaseDate());
        $this->assertNull($snapshot->musicVideoLink());
    }
}
