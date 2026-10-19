<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Infrastructure\Repository;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;
use ValueError;

#[Group('useDb')]
class PrincipalStatusPersistenceTest extends TestCase
{
    public function testMigrationDefaultsAndStatusRoundTrip(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();
        $principalId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create($accountId);
        CreateIdentity::create(new IdentityIdentifier($identityId));
        DB::table('account_principals')->insert([
            'id' => (string) $principalId,
            'identity_id' => $identityId,
            'account_id' => $accountId,
        ]);
        self::assertSame('active', DB::table('account_principals')->where('id', (string) $principalId)->value('status'));
        /** @var PrincipalRepositoryInterface $repository */
        $repository = $this->app()->make(PrincipalRepositoryInterface::class);
        $principal = $repository->findById($principalId);
        self::assertNotNull($principal);
        self::assertSame(PrincipalStatus::ACTIVE, $principal->status());
        $principal->changeStatus(PrincipalStatus::SUSPENDED);
        $repository->save($principal);
        self::assertSame(PrincipalStatus::SUSPENDED, $repository->findById($principalId)?->status());
        self::assertSame(PrincipalStatus::SUSPENDED, $repository->findByIds([$principalId])[(string) $principalId]->status());
        self::assertSame(PrincipalStatus::SUSPENDED, $repository->findByIdentityIdentifierAndAccountIdentifier($principal->identityIdentifier(), $principal->accountIdentifier())?->status());
        $principal->changeStatus(PrincipalStatus::ACTIVE);
        $repository->save($principal);
        self::assertSame(PrincipalStatus::ACTIVE, $repository->findById($principalId)?->status());
        DB::table('account_principals')->where('id', (string) $principalId)->update(['status' => 'forced_stop']);
        self::assertSame(PrincipalStatus::FORCED_STOP, $repository->findById($principalId)->status());
        self::assertSame(PrincipalStatus::FORCED_STOP, $repository->findAllByIdentityIdentifier($principal->identityIdentifier())[0]->status());
        $forcedStop = $repository->findById($principalId);
        $repository->save($forcedStop);
        self::assertSame('forced_stop', DB::table('account_principals')->where('id', (string) $principalId)->value('status'));

        DB::table('account_principals')->where('id', (string) $principalId)->update(['status' => 'unknown']);
        $this->expectException(ValueError::class);
        $repository->findById($principalId);
    }
}
