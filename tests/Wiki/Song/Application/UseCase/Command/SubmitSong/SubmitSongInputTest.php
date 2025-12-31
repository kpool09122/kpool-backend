<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\SubmitSong;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Application\UseCase\Command\SubmitSong\SubmitSongInput;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitSongInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new SubmitSongInput(
            $songIdentifier,
            $principalIdentifier,
        );

        $this->assertSame((string)$songIdentifier, (string)$input->songIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}
