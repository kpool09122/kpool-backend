<?php

declare(strict_types=1);

namespace Tests\Wiki\Member\Application\UseCase\Command\SubmitMember;

use Source\Wiki\Member\Application\UseCase\Command\SubmitMember\SubmitMemberInput;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitMemberInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $memberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $input = new SubmitMemberInput(
            $memberIdentifier,
        );
        $this->assertSame((string)$memberIdentifier, (string)$input->memberIdentifier());
    }
}
