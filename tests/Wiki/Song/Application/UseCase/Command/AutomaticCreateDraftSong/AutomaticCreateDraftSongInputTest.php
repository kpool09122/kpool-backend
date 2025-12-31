<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong\AutomaticCreateDraftSongInput;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\AutomaticDraftSongCreationPayload;
use Source\Wiki\Song\Domain\ValueObject\AutomaticDraftSongSource;
use Source\Wiki\Song\Domain\ValueObject\BelongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\StrTestHelper;

class AutomaticCreateDraftSongInputTest extends TestCase
{
    public function testAccessors(): void
    {
        $payload = new AutomaticDraftSongCreationPayload(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::JAPANESE,
            new SongName('Auto Song'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [new BelongIdentifier(StrTestHelper::generateUuid())],
            new Lyricist('Auto Lyricist'),
            new Composer('Auto Composer'),
            new ReleaseDate(new DateTimeImmutable('2023-05-10')),
            new Overview('Auto-generated overview.'),
            new AutomaticDraftSongSource('webhook::song'),
        );
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new AutomaticCreateDraftSongInput($payload, $principalIdentifier);

        $this->assertSame($payload, $input->payload());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}
