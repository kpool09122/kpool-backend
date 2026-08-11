<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Domain\ValueObject;

use Source\Account\Principal\Domain\ValueObject\Action;
use Tests\TestCase;

class ActionTest extends TestCase
{
    public function testReadValue(): void
    {
        $this->assertSame('account:read', Action::READ->value);
    }

    public function testInviteMemberValue(): void
    {
        $this->assertSame('account:member:invite', Action::INVITE_MEMBER->value);
    }

    public function testAccountCategoryChangeRequestManageValue(): void
    {
        $this->assertSame('account:category-change-request:manage', Action::ACCOUNT_CATEGORY_CHANGE_REQUEST_MANAGE->value);
    }
}
