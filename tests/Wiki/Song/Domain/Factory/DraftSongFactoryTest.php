<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Domain\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Song\Domain\Factory\DraftSongFactory;
use Source\Wiki\Song\Domain\Factory\DraftSongFactoryInterface;
use Source\Wiki\Song\Domain\ValueObject\SongName;
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
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $songFactory = $this->app->make(DraftSongFactoryInterface::class);
        $song = $songFactory->create($editorIdentifier, $translation, $name);
        $this->assertTrue(UlidValidator::isValid((string)$song->songIdentifier()));
        $this->assertNull($song->publishedSongIdentifier());
        $this->assertSame((string)$editorIdentifier, (string)$song->editorIdentifier());
        $this->assertSame($translation->value, $song->translation()->value);
        $this->assertSame((string)$name, (string)$song->name());
        $this->assertSame([], $song->belongIdentifiers());
        $this->assertSame('', (string)$song->lyricist());
        $this->assertSame('', (string)$song->composer());
        $this->assertSame('', (string)$song->overView());
        $this->assertNull($song->coverImagePath());
        $this->assertNull($song->musicVideoLink());
        $this->assertSame(ApprovalStatus::Pending, $song->status());
    }
}
