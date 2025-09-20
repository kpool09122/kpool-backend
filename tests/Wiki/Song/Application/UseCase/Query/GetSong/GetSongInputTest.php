<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Query\GetSong;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Song\Application\UseCase\Query\GetSong\GetSongInput;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetSongInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $input = new GetSongInput($songIdentifier, $translation);
        $this->assertSame((string)$songIdentifier, (string)$input->songIdentifier());
        $this->assertSame($translation->value, $input->translation()->value);
    }
}
