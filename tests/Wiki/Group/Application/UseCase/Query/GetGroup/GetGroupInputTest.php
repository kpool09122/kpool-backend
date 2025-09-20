<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Query\GetGroup;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Group\Application\UseCase\Query\GetGroup\GetGroupInput;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetGroupInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $input = new GetGroupInput($groupIdentifier, $translation);
        $this->assertSame((string)$groupIdentifier, (string)$input->groupIdentifier());
        $this->assertSame($translation->value, $input->translation()->value);
    }
}
