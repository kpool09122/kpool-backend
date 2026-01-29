<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\AutomaticDraftTalentCreationPayload;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\StrTestHelper;

class AutomaticDraftTalentCreationPayloadTest extends TestCase
{
    public function test__construct(): void
    {
        $language = Language::JAPANESE;
        $name = new TalentName('山田 太郎');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new GroupIdentifier(StrTestHelper::generateUuid()),
        ];

        $payload = new AutomaticDraftTalentCreationPayload(
            $language,
            $name,
            $agencyIdentifier,
            $groupIdentifiers,
        );

        $this->assertSame($language, $payload->language());
        $this->assertSame($name, $payload->name());
        $this->assertSame($agencyIdentifier, $payload->agencyIdentifier());
        $this->assertSame($groupIdentifiers, $payload->groupIdentifiers());
    }

    public function testAllowsOptionalFields(): void
    {
        $payload = new AutomaticDraftTalentCreationPayload(
            Language::ENGLISH,
            new TalentName('Sample Talent'),
            null,
            [],
        );

        $this->assertNull($payload->agencyIdentifier());
        $this->assertSame([], $payload->groupIdentifiers());
    }
}
