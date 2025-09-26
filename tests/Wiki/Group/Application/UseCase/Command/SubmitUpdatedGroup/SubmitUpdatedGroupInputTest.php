<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\SubmitUpdatedGroup;

use Source\Wiki\Group\Application\UseCase\Command\SubmitUpdatedGroup\SubmitUpdatedGroupInput;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitUpdatedGroupInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $input = new SubmitUpdatedGroupInput(
            $groupIdentifier,
        );
        $this->assertSame((string)$groupIdentifier, (string)$input->groupIdentifier());
    }
}
