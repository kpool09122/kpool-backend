<?php

declare(strict_types=1);

namespace Tests\Wiki\Member\Application\UseCase\Command\PublishMember;

use Source\Wiki\Member\Application\UseCase\Command\PublishMember\PublishMemberInput;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
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

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new PublishMemberInput(
            $memberIdentifier,
            $publishedMemberIdentifier,
            $principal,
        );
        $this->assertSame((string)$memberIdentifier, (string)$input->memberIdentifier());
        $this->assertSame((string)$publishedMemberIdentifier, (string)$input->publishedMemberIdentifier());
        $this->assertSame($principal, $input->principal());
    }
}
