<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\SubmitGroup;

use Source\Wiki\Group\Application\UseCase\Command\SubmitGroup\SubmitGroupInput;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitGroupInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new SubmitGroupInput(
            $groupIdentifier,
            $principalIdentifier,
        );

        $this->assertSame((string)$groupIdentifier, (string)$input->groupIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}
