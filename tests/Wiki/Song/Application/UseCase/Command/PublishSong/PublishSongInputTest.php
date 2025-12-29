<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\PublishSong;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Application\UseCase\Command\PublishSong\PublishSongInput;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PublishSongInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());

        $input = new PublishSongInput(
            $songIdentifier,
            $publishedSongIdentifier,
            $principalIdentifier,
        );
        $this->assertSame((string)$songIdentifier, (string)$input->songIdentifier());
        $this->assertSame((string)$publishedSongIdentifier, (string)$input->publishedSongIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}
