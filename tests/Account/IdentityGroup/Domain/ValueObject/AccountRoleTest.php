<?php

declare(strict_types=1);

namespace Tests\Account\IdentityGroup\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;

class AccountRoleTest extends TestCase
{
    /**
     * 正常系: プロダクトオーナーのみがアカウントの本人認証をレビューできること.
     *
     * @return void
     */
    public function testCanReviewVerification(): void
    {
        $this->assertTrue(AccountRole::PRODUCT_OWNER->canReviewVerification());

        $this->assertFalse(AccountRole::ADMIN->canReviewVerification());
        $this->assertFalse(AccountRole::MEMBER->canReviewVerification());
        $this->assertFalse(AccountRole::BILLING_CONTACT->canReviewVerification());
        $this->assertFalse(AccountRole::OWNER->canReviewVerification());
    }
}
