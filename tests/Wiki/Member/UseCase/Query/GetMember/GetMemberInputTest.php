<?php

namespace Tests\Wiki\Member\UseCase\Query\GetMember;

use Businesses\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Businesses\Wiki\Member\UseCase\Query\GetMember\GetMemberInput;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetMemberInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $memberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $input = new GetMemberInput($memberIdentifier);
        $this->assertSame((string)$memberIdentifier, (string)$input->memberIdentifier());
    }
}
