<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Domain\ValueObject;

use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Condition;
use Source\Account\Principal\Domain\ValueObject\ConditionClause;
use Source\Account\Principal\Domain\ValueObject\ConditionKey;
use Source\Account\Principal\Domain\ValueObject\ConditionOperator;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\Statement;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class StatementTest extends TestCase
{
    public function testConstructAndAccessors(): void
    {
        $condition = new Condition([
            new ConditionClause(
                ConditionKey::RESOURCE_ACCOUNT_TYPE,
                ConditionOperator::EQUALS,
                AccountType::CORPORATION->value,
            ),
        ]);
        $statement = new Statement(
            Effect::DENY,
            [Action::UPDATE],
            [ResourceType::ACCOUNT],
            $condition,
        );

        $this->assertSame(Effect::DENY, $statement->effect());
        $this->assertSame([Action::UPDATE], $statement->actions());
        $this->assertSame([ResourceType::ACCOUNT], $statement->resourceTypes());
        $this->assertSame($condition, $statement->condition());
    }

    public function testResourceFactoryCreatesResource(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $resource = Resource::account($accountIdentifier, AccountType::CORPORATION);

        $this->assertSame(ResourceType::ACCOUNT, $resource->type());
        $this->assertSame($accountIdentifier, $resource->accountIdentifier());
        $this->assertSame(AccountType::CORPORATION, $resource->accountType());
    }
}
