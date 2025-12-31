<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\AutomaticDraftGroupCreationPayload;
use Source\Wiki\Group\Domain\ValueObject\AutomaticDraftGroupSource;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class AutomaticDraftGroupCreationPayloadTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成されること.
     */
    public function test__construct(): void
    {
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $groupName = new GroupName('TWICE');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $description = new Description('auto generated group profile');
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUuid()),
            new SongIdentifier(StrTestHelper::generateUuid()),
        ];
        $source = new AutomaticDraftGroupSource('news::source-id');
        $payload = new AutomaticDraftGroupCreationPayload(
            $editorIdentifier,
            $language,
            $groupName,
            $agencyIdentifier,
            $description,
            $songIdentifiers,
            $source,
        );

        $this->assertSame($editorIdentifier, $payload->editorIdentifier());
        $this->assertSame($language, $payload->translation());
        $this->assertSame($groupName, $payload->name());
        $this->assertSame($agencyIdentifier, $payload->agencyIdentifier());
        $this->assertSame($description, $payload->description());
        $this->assertSame($songIdentifiers, $payload->songIdentifiers());
        $this->assertSame($source, $payload->source());
    }
}
