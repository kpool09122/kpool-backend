<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Domain\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Song\Domain\Factory\SongFactory;
use Source\Wiki\Song\Domain\Factory\SongFactoryInterface;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SongFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $songFactory = $this->app->make(SongFactoryInterface::class);
        $this->assertInstanceOf(SongFactory::class, $songFactory);
    }

    /**
     * 正常系: Song Entityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $songFactory = $this->app->make(SongFactoryInterface::class);
        $song = $songFactory->create($translationSetIdentifier, $translation, $name);
        $this->assertTrue(UlidValidator::isValid((string)$song->songIdentifier()));
        $this->assertSame((string)$translationSetIdentifier, (string)$song->translationSetIdentifier());
        $this->assertSame($translation->value, $song->translation()->value);
        $this->assertSame((string)$name, (string)$song->name());
        $this->assertNull($song->agencyIdentifier());
        $this->assertSame([], $song->belongIdentifiers());
        $this->assertSame('', (string)$song->lyricist());
        $this->assertSame('', (string)$song->composer());
        $this->assertSame('', (string)$song->overView());
        $this->assertNull($song->coverImagePath());
        $this->assertNull($song->musicVideoLink());
    }
}
