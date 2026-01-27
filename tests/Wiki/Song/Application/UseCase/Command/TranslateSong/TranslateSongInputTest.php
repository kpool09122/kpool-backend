<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\TranslateSong;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Application\UseCase\Command\TranslateSong\TranslateSongInput;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateSongInputTest extends TestCase
{
    public function test__construct(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new TranslateSongInput($songIdentifier, $principalIdentifier);
        $this->assertSame((string) $songIdentifier, (string) $input->songIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertNull($input->publishedSongIdentifier());
    }

    public function testWithPublishedSongIdentifier(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUuid());

        $input = new TranslateSongInput($songIdentifier, $principalIdentifier, $publishedSongIdentifier);
        $this->assertSame((string) $songIdentifier, (string) $input->songIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame((string) $publishedSongIdentifier, (string) $input->publishedSongIdentifier());
    }
}
