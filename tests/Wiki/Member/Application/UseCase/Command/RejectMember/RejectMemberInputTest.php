<?php

declare(strict_types=1);

namespace Tests\Wiki\Member\Application\UseCase\Command\RejectMember;

use Source\Wiki\Member\Application\UseCase\Command\RejectMember\RejectMemberInput;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectMemberInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $memberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $input = new RejectMemberInput(
            $memberIdentifier,
        );
        $this->assertSame((string)$memberIdentifier, (string)$input->memberIdentifier());
    }
}
