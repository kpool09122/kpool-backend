<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\PolicyEvaluation;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class PolicyEvaluationTest extends TestCase
{
    public function testDistinguishesPersistedGroupsFromAnExplicitlyEmptyFinalSet(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $accountIdentifier);
        $resource = Resource::account($accountIdentifier);
        $persisted = new PolicyEvaluation($principal, Action::DELEGATION_ACCOUNT_SWITCH, $resource);
        $removed = new PolicyEvaluation($principal, Action::DELEGATION_ACCOUNT_SWITCH, $resource, []);
        self::assertNull($persisted->principalGroups);
        self::assertSame([], $removed->principalGroups);
        self::assertSame($principal, $removed->principal);
        self::assertSame($resource, $removed->resource);
        self::assertSame(Action::DELEGATION_ACCOUNT_SWITCH, $removed->action);
    }
}
