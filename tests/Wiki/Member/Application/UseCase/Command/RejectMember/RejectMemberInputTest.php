<?php

declare(strict_types=1);

namespace Tests\Wiki\Member\Application\UseCase\Command\RejectMember;

use Source\Wiki\Member\Application\UseCase\Command\RejectMember\RejectMemberInput;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
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

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new RejectMemberInput(
            $memberIdentifier,
            $principal,
        );
        $this->assertSame((string)$memberIdentifier, (string)$input->memberIdentifier());
        $this->assertSame($principal, $input->principal());
    }
}
