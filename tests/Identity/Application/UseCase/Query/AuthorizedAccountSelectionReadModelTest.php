<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Query\AuthenticatedAccountReferenceReadModel;
use Source\Identity\Application\UseCase\Query\AuthorizedAccountSelectionReadModel;
use Source\Identity\Application\UseCase\Query\SwitchableAccountReadModel;

class AuthorizedAccountSelectionReadModelTest extends TestCase
{
    public function testUnavailableSelectionHasOnlyNullableAccountMetadataAndEmptyCandidates(): void
    {
        self::assertSame(['identityIdentifier' => 'identity', 'accountIdentifier' => null, 'accountPrincipalIdentifier' => null, 'account' => null, 'delegationIdentifier' => null, 'originalAccount' => null, 'switchableAccounts' => []], (new AuthorizedAccountSelectionReadModel('identity', null, null, null, null, []))->toArray());
    }

    public function testOwnTargetUsesNullDelegationAndOnlyAccountReferences(): void
    {
        $account = new AuthenticatedAccountReferenceReadModel('account', 'name');
        self::assertSame(['identityIdentifier' => 'identity', 'accountIdentifier' => 'account', 'accountPrincipalIdentifier' => 'principal', 'account' => $account->toArray(), 'delegationIdentifier' => null, 'originalAccount' => $account->toArray(), 'switchableAccounts' => [['delegationIdentifier' => null, 'accountIdentifier' => 'account', 'account' => $account->toArray(), 'isCurrent' => true]]], (new AuthorizedAccountSelectionReadModel('identity', $account, 'principal', null, $account, [new SwitchableAccountReadModel(null, 'account', $account, true)]))->toArray());
    }
}
