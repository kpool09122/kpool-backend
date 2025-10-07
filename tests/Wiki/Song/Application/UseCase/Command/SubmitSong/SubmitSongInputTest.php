<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\SubmitSong;

use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
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
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new SubmitSongInput(
            $songIdentifier,
            $principal,
        );

        $this->assertSame((string)$songIdentifier, (string)$input->songIdentifier());
        $this->assertSame($principal, $input->principal());
    }
}
