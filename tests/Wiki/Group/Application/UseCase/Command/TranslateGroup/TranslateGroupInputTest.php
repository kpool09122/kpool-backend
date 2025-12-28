<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\TranslateGroup;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Group\Application\UseCase\Command\TranslateGroup\TranslateGroupInput;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateGroupInputTest extends TestCase
{
    public function test__construct(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $input = new TranslateGroupInput($groupIdentifier, $principal);

        $this->assertSame((string)$groupIdentifier, (string)$input->groupIdentifier());
        $this->assertSame($principal, $input->principal());
    }
}
