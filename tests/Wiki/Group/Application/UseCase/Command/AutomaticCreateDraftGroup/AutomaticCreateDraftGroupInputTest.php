<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\AutomaticCreateDraftGroup;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Group\Application\UseCase\Command\AutomaticCreateDraftGroup\AutomaticCreateDraftGroupInput;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\AutomaticDraftGroupCreationPayload;
use Source\Wiki\Group\Domain\ValueObject\AutomaticDraftGroupSource;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class AutomaticCreateDraftGroupInputTest extends TestCase
{
    public function testAccessors(): void
    {
        $payload = new AutomaticDraftGroupCreationPayload(
            new EditorIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new GroupName('TWICE'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new Description('auto generated'),
            [new SongIdentifier(StrTestHelper::generateUuid())],
            new AutomaticDraftGroupSource('webhook::draft'),
        );

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new AutomaticCreateDraftGroupInput($payload, $principalIdentifier);

        $this->assertSame($payload, $input->payload());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}
