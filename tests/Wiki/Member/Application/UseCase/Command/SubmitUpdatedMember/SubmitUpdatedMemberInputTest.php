<?php

declare(strict_types=1);

namespace Tests\Wiki\Member\Application\UseCase\Command\SubmitUpdatedMember;

use Source\Wiki\Member\Application\UseCase\Command\SubmitUpdatedMember\SubmitUpdatedMemberInput;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitUpdatedMemberInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $memberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $input = new SubmitUpdatedMemberInput(
            $memberIdentifier,
        );
        $this->assertSame((string)$memberIdentifier, (string)$input->memberIdentifier());
    }
}
