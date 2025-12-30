<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\RejectGroup;

use Source\Wiki\Group\Application\UseCase\Command\RejectGroup\RejectGroupInput;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectGroupInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());

        $input = new RejectGroupInput(
            $groupIdentifier,
            $principalIdentifier,
        );

        $this->assertSame((string)$groupIdentifier, (string)$input->groupIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}
