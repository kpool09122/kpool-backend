<?php

declare(strict_types=1);

namespace Tests\Wiki\Member\Application\UseCase\Command\PublishMember;

use Source\Wiki\Member\Application\UseCase\Command\PublishMember\PublishMemberInput;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PublishMemberInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $memberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $publishedMemberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $input = new PublishMemberInput(
            $memberIdentifier,
            $publishedMemberIdentifier,
        );
        $this->assertSame((string)$memberIdentifier, (string)$input->memberIdentifier());
        $this->assertSame((string)$publishedMemberIdentifier, (string)$input->publishedMemberIdentifier());
    }
}
