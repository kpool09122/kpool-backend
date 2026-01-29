<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\AutomaticDraftGroupCreationPayload;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Tests\Helper\StrTestHelper;

class AutomaticDraftGroupCreationPayloadTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成されること.
     */
    public function test__construct(): void
    {
        $language = Language::KOREAN;
        $groupName = new GroupName('TWICE');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $payload = new AutomaticDraftGroupCreationPayload(
            $language,
            $groupName,
            $agencyIdentifier,
        );

        $this->assertSame($language, $payload->language());
        $this->assertSame($groupName, $payload->name());
        $this->assertSame($agencyIdentifier, $payload->agencyIdentifier());
    }
}
