<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Account\Account\Domain\Factory\AccountFactoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Account\Infrastructure\Factory\AccountFactory;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Email;
use Tests\TestCase;

class AccountFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(AccountFactoryInterface::class);
        $this->assertInstanceOf(AccountFactory::class, $factory);
    }

    /**
     * 正常系: 正しくAccountエンティティが作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $email = new Email('test@test.com');
        $accountType = AccountType::CORPORATION;
        $accountName = new AccountName('Example Inc');

        $factory = $this->app->make(AccountFactoryInterface::class);
        $account = $factory->create(
            $email,
            $accountType,
            $accountName,
        );

        $this->assertTrue(UuidValidator::isValid((string) $account->accountIdentifier()));
        $this->assertSame($email, $account->email());
        $this->assertSame($accountType, $account->type());
        $this->assertSame($accountName, $account->name());
        $this->assertSame(AccountCategory::GENERAL, $account->accountCategory());
        $this->assertEquals(DeletionReadinessChecklist::ready(), $account->deletionReadiness());
    }
}
