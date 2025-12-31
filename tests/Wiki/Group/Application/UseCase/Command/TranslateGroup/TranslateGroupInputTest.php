<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\TranslateGroup;

use Source\Wiki\Group\Application\UseCase\Command\TranslateGroup\TranslateGroupInput;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateGroupInputTest extends TestCase
{
    public function test__construct(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new TranslateGroupInput($groupIdentifier, $principalIdentifier);

        $this->assertSame((string)$groupIdentifier, (string)$input->groupIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}
