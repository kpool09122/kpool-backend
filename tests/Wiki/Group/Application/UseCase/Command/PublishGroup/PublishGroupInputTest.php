<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\PublishGroup;

use Source\Wiki\Group\Application\UseCase\Command\PublishGroup\PublishGroupInput;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PublishGroupInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $input = new PublishGroupInput(
            $groupIdentifier,
            $publishedGroupIdentifier,
        );
        $this->assertSame((string)$groupIdentifier, (string)$input->groupIdentifier());
        $this->assertSame((string)$publishedGroupIdentifier, (string)$input->publishedGroupIdentifier());
    }
}
