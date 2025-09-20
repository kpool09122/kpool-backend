<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\UseCase\Query\GetSong;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Businesses\Wiki\Song\UseCase\Query\GetSong\GetSongInput;
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
