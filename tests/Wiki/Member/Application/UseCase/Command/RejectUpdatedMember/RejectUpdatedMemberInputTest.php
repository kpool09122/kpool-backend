<?php

declare(strict_types=1);

namespace Tests\Wiki\Member\Application\UseCase\Command\RejectUpdatedMember;

use Source\Wiki\Member\Application\UseCase\Command\RejectUpdatedMember\RejectUpdatedMemberInput;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectUpdatedMemberInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $memberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $input = new RejectUpdatedMemberInput(
            $memberIdentifier,
        );
        $this->assertSame((string)$memberIdentifier, (string)$input->memberIdentifier());
    }
}
