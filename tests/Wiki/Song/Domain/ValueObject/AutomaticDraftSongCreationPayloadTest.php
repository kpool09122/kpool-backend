<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\AutomaticDraftSongCreationPayload;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\StrTestHelper;

class AutomaticDraftSongCreationPayloadTest extends TestCase
{
    public function test__construct(): void
    {
        $language = Language::ENGLISH;
        $name = new SongName('Sample Song');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());

        $payload = new AutomaticDraftSongCreationPayload(
            $language,
            $name,
            $agencyIdentifier,
            $groupIdentifier,
            $talentIdentifier,
        );

        $this->assertSame($language, $payload->language());
        $this->assertSame($name, $payload->name());
        $this->assertSame($agencyIdentifier, $payload->agencyIdentifier());
        $this->assertSame($groupIdentifier, $payload->groupIdentifier());
        $this->assertSame($talentIdentifier, $payload->talentIdentifier());
    }

    public function testAllowsOptionalFields(): void
    {
        $payload = new AutomaticDraftSongCreationPayload(
            Language::JAPANESE,
            new SongName('Optional Song'),
            null,
            null,
            null,
        );

        $this->assertNull($payload->agencyIdentifier());
        $this->assertNull($payload->groupIdentifier());
        $this->assertNull($payload->talentIdentifier());
    }
}
