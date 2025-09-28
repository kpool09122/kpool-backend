<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\RejectUpdatedSong;

use Source\Wiki\Song\Application\UseCase\Command\RejectUpdatedSong\RejectUpdatedSongInput;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectUpdatedSongInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $input = new RejectUpdatedSongInput(
            $songIdentifier,
        );
        $this->assertSame((string)$songIdentifier, (string)$input->songIdentifier());
    }
}
