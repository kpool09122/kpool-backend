<?php

declare(strict_types=1);

namespace Tests\Wiki\Member\Application\UseCase\Command\TranslateMember;

use Source\Wiki\Member\Application\UseCase\Command\TranslateMember\TranslateMemberInput;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateMemberInputTest extends TestCase
{
    public function test__construct(): void
    {
        $memberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $input = new TranslateMemberInput($memberIdentifier);
        $this->assertSame((string)$memberIdentifier, (string)$input->memberIdentifier());
    }
}
