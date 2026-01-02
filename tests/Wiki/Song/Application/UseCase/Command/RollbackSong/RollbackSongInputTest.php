<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\RollbackSong;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Application\UseCase\Command\RollbackSong\RollbackSongInput;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RollbackSongInputTest extends TestCase
{
    public function testConstruct(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(3);

        $input = new RollbackSongInput(
            $principalIdentifier,
            $songIdentifier,
            $targetVersion,
        );

        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($songIdentifier, $input->songIdentifier());
        $this->assertSame($targetVersion, $input->targetVersion());
    }
}
