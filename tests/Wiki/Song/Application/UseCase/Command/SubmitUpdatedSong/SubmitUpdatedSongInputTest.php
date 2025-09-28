<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\SubmitUpdatedSong;

use Source\Wiki\Song\Application\UseCase\Command\SubmitUpdatedSong\SubmitUpdatedSongInput;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitUpdatedSongInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $input = new SubmitUpdatedSongInput(
            $songIdentifier,
        );
        $this->assertSame((string)$songIdentifier, (string)$input->songIdentifier());
    }
}
