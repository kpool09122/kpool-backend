<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
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
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new SongName('TT');
        $songFactory = $this->app->make(DraftSongFactoryInterface::class);
        $song = $songFactory->create($editorIdentifier, $language, $name);
        $this->assertTrue(UuidValidator::isValid((string)$song->songIdentifier()));
        $this->assertNull($song->publishedSongIdentifier());
        $this->assertTrue(UuidValidator::isValid((string)$song->translationSetIdentifier()));
        $this->assertSame((string)$editorIdentifier, (string)$song->editorIdentifier());
        $this->assertSame($language->value, $song->language()->value);
        $this->assertSame((string)$name, (string)$song->name());
        $this->assertNull($song->agencyIdentifier());
        $this->assertSame([], $song->belongIdentifiers());
        $this->assertSame('', (string)$song->lyricist());
        $this->assertSame('', (string)$song->composer());
        $this->assertSame('', (string)$song->overView());
        $this->assertNull($song->coverImagePath());
        $this->assertNull($song->musicVideoLink());
        $this->assertSame(ApprovalStatus::Pending, $song->status());
    }
}
