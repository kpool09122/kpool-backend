<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\TranslateGroup;

use Source\Wiki\Group\Application\UseCase\Command\TranslateGroup\TranslateGroupInput;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateGroupInputTest extends TestCase
{
    public function test__construct(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $input = new TranslateGroupInput($groupIdentifier);
        $this->assertSame((string)$groupIdentifier, (string)$input->groupIdentifier());
    }
}
