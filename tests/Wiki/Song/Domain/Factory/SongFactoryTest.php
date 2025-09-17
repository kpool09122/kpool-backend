<?php

namespace Tests\Wiki\Song\Domain\Factory;

use Businesses\Shared\Service\Ulid\UlidValidator;
use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Song\Domain\Factory\SongFactory;
use Businesses\Wiki\Song\Domain\Factory\SongFactoryInterface;
use Businesses\Wiki\Song\Domain\ValueObject\SongName;
use Illuminate\Contracts\Container\BindingResolutionException;
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
     * 正常系: Member Entityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $songFactory = $this->app->make(SongFactoryInterface::class);
        $song = $songFactory->create($translation, $name);
        $this->assertTrue(UlidValidator::isValid((string)$song->songIdentifier()));
        $this->assertSame($translation->value, $song->translation()->value);
        $this->assertSame((string)$name, (string)$song->name());
        $this->assertSame([], $song->belongIdentifiers());
        $this->assertSame('', (string)$song->lyricist());
        $this->assertSame('', (string)$song->composer());
        $this->assertSame('', (string)$song->overView());
        $this->assertNull($song->coverImagePath());
        $this->assertNull($song->musicVideoLink());
    }
}
