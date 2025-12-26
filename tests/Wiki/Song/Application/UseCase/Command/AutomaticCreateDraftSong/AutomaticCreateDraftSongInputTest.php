<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
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
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Language::JAPANESE,
            new SongName('Auto Song'),
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            [new BelongIdentifier(StrTestHelper::generateUlid())],
            new Lyricist('Auto Lyricist'),
            new Composer('Auto Composer'),
            new ReleaseDate(new DateTimeImmutable('2023-05-10')),
            new Overview('Auto-generated overview.'),
            new AutomaticDraftSongSource('webhook::song'),
        );
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUlid()),
            new IdentityIdentifier(StrTestHelper::generateUlid()),
            Role::ADMINISTRATOR,
            null,
            [],
            [],
        );

        $input = new AutomaticCreateDraftSongInput($payload, $principal);

        $this->assertSame($payload, $input->payload());
        $this->assertSame($principal, $input->principal());
    }
}
