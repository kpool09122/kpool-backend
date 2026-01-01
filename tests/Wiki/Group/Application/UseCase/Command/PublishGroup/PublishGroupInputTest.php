<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\PublishGroup;

use Source\Wiki\Group\Application\UseCase\Command\PublishGroup\PublishGroupInput;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
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
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new PublishGroupInput(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $principalIdentifier,
        );
        $this->assertSame((string)$groupIdentifier, (string)$input->groupIdentifier());
        $this->assertSame((string)$publishedGroupIdentifier, (string)$input->publishedGroupIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}
