<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\AutomaticCreateDraftGroup;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Group\Application\UseCase\Command\AutomaticCreateDraftGroup\AutomaticCreateDraftGroupInput;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\AutomaticDraftGroupCreationPayload;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class AutomaticCreateDraftGroupInputTest extends TestCase
{
    public function testAccessors(): void
    {
        $payload = new AutomaticDraftGroupCreationPayload(
            Language::KOREAN,
            new GroupName('TWICE'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
        );

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new AutomaticCreateDraftGroupInput($payload, $principalIdentifier);

        $this->assertSame($payload, $input->payload());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}
