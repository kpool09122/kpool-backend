<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Account\Account\Domain\Factory\AccountCategoryChangeRequestFactoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;
use Source\Account\Account\Infrastructure\Factory\AccountCategoryChangeRequestFactory;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountCategoryChangeRequestFactoryTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(AccountCategoryChangeRequestFactoryInterface::class);

        $this->assertInstanceOf(AccountCategoryChangeRequestFactory::class, $factory);
    }

    /**
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $currentAccountCategory = AccountCategory::GENERAL;
        $requestedAccountCategory = AccountCategory::AGENCY;

        $factory = $this->app->make(AccountCategoryChangeRequestFactoryInterface::class);
        $request = $factory->create($accountIdentifier, $currentAccountCategory, $requestedAccountCategory);

        $this->assertTrue(UuidValidator::isValid((string) $request->requestIdentifier()));
        $this->assertSame((string) $accountIdentifier, (string) $request->accountIdentifier());
        $this->assertSame($currentAccountCategory, $request->currentAccountCategory());
        $this->assertSame($requestedAccountCategory, $request->requestedAccountCategory());
        $this->assertSame(AccountCategoryChangeRequestStatus::PENDING, $request->status());
        $this->assertNull($request->reviewedBy());
        $this->assertNull($request->reviewedAt());
        $this->assertNull($request->rejectionReason());
    }
}
