<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Domain\ValueObject;

use Source\Account\Principal\Domain\ValueObject\Action;
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
        $statement = new Statement(
            Effect::DENY,
            [Action::DELETE],
            [ResourceType::ACCOUNT],
        );

        $this->assertSame(Effect::DENY, $statement->effect());
        $this->assertSame([Action::DELETE], $statement->actions());
        $this->assertSame([ResourceType::ACCOUNT], $statement->resourceTypes());
    }

    public function testResourceFactoryCreatesResource(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $resource = Resource::account($accountIdentifier);

        $this->assertSame(ResourceType::ACCOUNT, $resource->type());
        $this->assertSame($accountIdentifier, $resource->accountIdentifier());
    }
}
