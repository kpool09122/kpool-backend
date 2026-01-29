<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\AutoCreateGroup;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Group\Application\UseCase\Command\AutoCreateGroup\AutoCreateGroupInput;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\AutoGroupCreationPayload;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class AutoCreateGroupInputTest extends TestCase
{
    public function testAccessors(): void
    {
        $payload = new AutoGroupCreationPayload(
            Language::KOREAN,
            new GroupName('TWICE'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
        );

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new AutoCreateGroupInput($payload, $principalIdentifier);

        $this->assertSame($payload, $input->payload());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}
