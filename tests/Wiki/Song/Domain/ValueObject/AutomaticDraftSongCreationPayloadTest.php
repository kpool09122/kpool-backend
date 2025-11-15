<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Domain\ValueObject;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
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

class AutomaticDraftSongCreationPayloadTest extends TestCase
{
    public function test__construct(): void
    {
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::ENGLISH;
        $name = new SongName('Sample Song');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $belongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $lyricist = new Lyricist('Composer One');
        $composer = new Composer('Arranger Two');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2020-01-01'));
        $overview = new Overview('Sample overview for automatic draft song.');
        $source = new AutomaticDraftSongSource('news::song-source');

        $payload = new AutomaticDraftSongCreationPayload(
            $editorIdentifier,
            $translation,
            $name,
            $agencyIdentifier,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overview,
            $source,
        );

        $this->assertSame($editorIdentifier, $payload->editorIdentifier());
        $this->assertSame($translation, $payload->translation());
        $this->assertSame($name, $payload->name());
        $this->assertSame($agencyIdentifier, $payload->agencyIdentifier());
        $this->assertSame($belongIdentifiers, $payload->belongIdentifiers());
        $this->assertSame($lyricist, $payload->lyricist());
        $this->assertSame($composer, $payload->composer());
        $this->assertSame($releaseDate, $payload->releaseDate());
        $this->assertSame($overview, $payload->overview());
        $this->assertSame($source, $payload->source());
    }

    public function testAllowsOptionalFields(): void
    {
        $payload = new AutomaticDraftSongCreationPayload(
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Translation::JAPANESE,
            new SongName('Optional Song'),
            null,
            [],
            new Lyricist('Lyricist'),
            new Composer('Composer'),
            null,
            new Overview('Optional overview.'),
            new AutomaticDraftSongSource('system::seed'),
        );

        $this->assertNull($payload->agencyIdentifier());
        $this->assertSame([], $payload->belongIdentifiers());
        $this->assertNull($payload->releaseDate());
    }
}
