<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\Exception\IdentityWithdrawalNotAllowedException;
use Source\Account\Account\Domain\Service\IdentityWithdrawalEligibility;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountCategory;

class IdentityWithdrawalEligibilityTest extends TestCase
{
    #[DataProvider('eligibilityCases')]
    public function testEligibility(AccountCategory $category, ?AccountType $type, bool $owner, bool $allowed): void
    {
        if (! $allowed) {
            $this->expectException(IdentityWithdrawalNotAllowedException::class);
        }

        (new IdentityWithdrawalEligibility())->assertAllowed($category, $type, $owner);

        if ($allowed) {
            $this->addToAssertionCount(1);
        }
    }

    /** @return array<string, array{AccountCategory, ?AccountType, bool, bool}> */
    public static function eligibilityCases(): array
    {
        return [
            'individual owner' => [AccountCategory::GENERAL, AccountType::INDIVIDUAL, true, true],
            'individual member' => [AccountCategory::GENERAL, AccountType::INDIVIDUAL, false, true],
            'corporation owner' => [AccountCategory::GENERAL, AccountType::CORPORATION, true, false],
            'corporation member' => [AccountCategory::GENERAL, AccountType::CORPORATION, false, true],
            'agency' => [AccountCategory::AGENCY, AccountType::INDIVIDUAL, false, false],
            'talent' => [AccountCategory::TALENT, AccountType::INDIVIDUAL, false, false],
            'unclassified account' => [AccountCategory::GENERAL, null, false, false],
        ];
    }
}
