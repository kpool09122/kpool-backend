<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\RollbackGroup;

use Source\Wiki\Group\Application\UseCase\Command\RollbackGroup\RollbackGroupInput;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RollbackGroupInputTest extends TestCase
{
    public function testConstruct(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(3);

        $input = new RollbackGroupInput(
            $principalIdentifier,
            $groupIdentifier,
            $targetVersion,
        );

        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($groupIdentifier, $input->groupIdentifier());
        $this->assertSame($targetVersion, $input->targetVersion());
    }
}
