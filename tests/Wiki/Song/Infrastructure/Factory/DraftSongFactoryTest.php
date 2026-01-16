<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Domain\Factory\DraftSongFactoryInterface;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Song\Infrastructure\Factory\DraftSongFactory;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DraftSongFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $songFactory = $this->app->make(DraftSongFactoryInterface::class);
        $this->assertInstanceOf(DraftSongFactory::class, $songFactory);
    }

    /**
     * 正常系: DraftSong Entityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new SongName('TT');
        $normalizationService = $this->app->make(NormalizationServiceInterface::class);
        $expectedNormalizedName = $normalizationService->normalize((string) $name, $language);
        $expectedNormalizedLyricist = $normalizationService->normalize('', $language);
        $expectedNormalizedComposer = $normalizationService->normalize('', $language);
        $songFactory = $this->app->make(DraftSongFactoryInterface::class);
        $song = $songFactory->create($editorIdentifier, $language, $name);
        $this->assertTrue(UuidValidator::isValid((string)$song->songIdentifier()));
        $this->assertNull($song->publishedSongIdentifier());
        $this->assertTrue(UuidValidator::isValid((string)$song->translationSetIdentifier()));
        $this->assertSame((string)$editorIdentifier, (string)$song->editorIdentifier());
        $this->assertSame($language->value, $song->language()->value);
        $this->assertSame((string)$name, (string)$song->name());
        $this->assertSame($expectedNormalizedName, $song->normalizedName());
        $this->assertNull($song->agencyIdentifier());
        $this->assertNull($song->groupIdentifier());
        $this->assertNull($song->talentIdentifier());
        $this->assertSame('', (string)$song->lyricist());
        $this->assertSame($expectedNormalizedLyricist, $song->normalizedLyricist());
        $this->assertSame('', (string)$song->composer());
        $this->assertSame($expectedNormalizedComposer, $song->normalizedComposer());
        $this->assertSame('', (string)$song->overView());
        $this->assertNull($song->coverImagePath());
        $this->assertNull($song->musicVideoLink());
        $this->assertSame(ApprovalStatus::Pending, $song->status());
    }
}
