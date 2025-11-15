<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Domain\ValueObject;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\AutomaticDraftTalentCreationPayload;
use Source\Wiki\Talent\Domain\ValueObject\AutomaticDraftTalentSource;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\StrTestHelper;

class AutomaticDraftTalentCreationPayloadTest extends TestCase
{
    public function test__construct(): void
    {
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::JAPANESE;
        $name = new TalentName('山田 太郎');
        $realName = new RealName('Yamada Taro');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1995-08-01'));
        $career = new Career('Lead vocalist of Sample Group.');
        $imagePath = new ImagePath('talents/taro.png');
        $relevantVideoLinks = new RelevantVideoLinks([
            new ExternalContentLink('https://example.com/video/1'),
        ]);
        $source = new AutomaticDraftTalentSource('webhook::talent');

        $payload = new AutomaticDraftTalentCreationPayload(
            $editorIdentifier,
            $translation,
            $name,
            $realName,
            $agencyIdentifier,
            $groupIdentifiers,
            $birthday,
            $career,
            $source,
        );

        $this->assertSame($editorIdentifier, $payload->editorIdentifier());
        $this->assertSame($translation, $payload->translation());
        $this->assertSame($name, $payload->name());
        $this->assertSame($realName, $payload->realName());
        $this->assertSame($agencyIdentifier, $payload->agencyIdentifier());
        $this->assertSame($groupIdentifiers, $payload->groupIdentifiers());
        $this->assertSame($birthday, $payload->birthday());
        $this->assertSame($career, $payload->career());
        $this->assertSame($source, $payload->source());
    }

    public function testAllowsOptionalFields(): void
    {
        $payload = new AutomaticDraftTalentCreationPayload(
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Translation::ENGLISH,
            new TalentName('Sample Talent'),
            new RealName('Sample Name'),
            null,
            [],
            null,
            new Career('Rookie idol'),
            new AutomaticDraftTalentSource('system::seed'),
        );

        $this->assertNull($payload->agencyIdentifier());
        $this->assertSame([], $payload->groupIdentifiers());
        $this->assertNull($payload->birthday());
    }
}
