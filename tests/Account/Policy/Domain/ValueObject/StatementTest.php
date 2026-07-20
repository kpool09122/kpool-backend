<?php

declare(strict_types=1);

namespace Tests\Account\Policy\Domain\ValueObject;

use Source\Account\Policy\Domain\ValueObject\AccountAction;
use Source\Account\Policy\Domain\ValueObject\AccountResource;
use Source\Account\Policy\Domain\ValueObject\AccountResourceType;
use Source\Account\Policy\Domain\ValueObject\Effect;
use Source\Account\Policy\Domain\ValueObject\Statement;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class StatementTest extends TestCase
{
    public function testConstructAndAccessors(): void
    {
        $statement = new Statement(
            Effect::DENY,
            [AccountAction::DELETE],
            [AccountResourceType::ACCOUNT],
        );

        $this->assertSame(Effect::DENY, $statement->effect());
        $this->assertSame([AccountAction::DELETE], $statement->actions());
        $this->assertSame([AccountResourceType::ACCOUNT], $statement->resourceTypes());
    }

    public function testAccountResourceFactoryCreatesAccountResource(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $resource = AccountResource::account($accountIdentifier);

        $this->assertSame(AccountResourceType::ACCOUNT, $resource->type());
        $this->assertSame($accountIdentifier, $resource->accountIdentifier());
    }
}
