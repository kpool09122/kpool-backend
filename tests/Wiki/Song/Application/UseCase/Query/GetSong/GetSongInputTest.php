<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Query\GetSong;

use Source\Shared\Domain\ValueObject\Language;
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
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $input = new GetSongInput($songIdentifier, $language);
        $this->assertSame((string)$songIdentifier, (string)$input->songIdentifier());
        $this->assertSame($language->value, $input->language()->value);
    }
}
