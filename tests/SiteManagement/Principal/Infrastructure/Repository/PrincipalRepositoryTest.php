<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Infrastructure\Repository;

use Application\Http\Context\AuthContextCache;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Infrastructure\Repository\PrincipalRepository;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class PrincipalRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        CreateAccount::create('00000000-0000-7000-8000-000000000009');
    }

    public function testSaveAndDeleteInvalidateContextAfterCommit(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $identity, new AccountIdentifier('00000000-0000-7000-8000-000000000009'));
        $invalidated = [];
        $cache = Mockery::mock(AuthContextCache::class);
        $cache->shouldReceive('forgetSiteManagement')->twice()->with($identity, Mockery::on(static fn (AccountIdentifier $account): bool => (string) $account === '00000000-0000-7000-8000-000000000009'))->andReturnUsing(
            static function (IdentityIdentifier $identifier) use (&$invalidated): void {
                $invalidated[] = (string) $identifier;
            },
        );
        $this->app()->instance(AuthContextCache::class, $cache);
        DB::commit();

        try {
            DB::beginTransaction();
            CreateIdentity::create($identity);
            $repository = new PrincipalRepository();
            $repository->save($principal);
            $repository->delete($principal);
            $this->assertSame([], $invalidated);
            DB::commit();
            $this->assertSame([(string) $identity, (string) $identity], $invalidated);
        } finally {
            DB::table('identities')->where('id', (string) $identity)->delete();
            DB::table('accounts')->where('id', '00000000-0000-7000-8000-000000000009')->delete();
            DB::beginTransaction();
        }
    }

    public function testIdentityChangeInvalidatesPreviousAndNewContexts(): void
    {
        $previousIdentity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $newIdentity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $id = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $cache = Mockery::mock(AuthContextCache::class);
        $cache->shouldReceive('forgetSiteManagement')->twice()->with(Mockery::on(
            static fn (IdentityIdentifier $identifier): bool => (string) $identifier === (string) $previousIdentity,
        ), Mockery::on(static fn (AccountIdentifier $account): bool => (string) $account === '00000000-0000-7000-8000-000000000009'));
        $cache->shouldReceive('forgetSiteManagement')->once()->with($newIdentity, Mockery::on(static fn (AccountIdentifier $account): bool => (string) $account === '00000000-0000-7000-8000-000000000009'));
        $this->app()->instance(AuthContextCache::class, $cache);
        DB::commit();

        try {
            DB::beginTransaction();
            CreateIdentity::create($previousIdentity);
            CreateIdentity::create($newIdentity, ['email' => 'new-identity@example.com']);
            $repository = new PrincipalRepository();
            $repository->save(new Principal($id, $previousIdentity, new AccountIdentifier('00000000-0000-7000-8000-000000000009')));
            $repository->save(new Principal($id, $newIdentity, new AccountIdentifier('00000000-0000-7000-8000-000000000009')));
            $this->assertSame((string) $newIdentity, (string) $repository->findById($id)?->identityIdentifier());
            DB::commit();
        } finally {
            DB::table('site_management_principals')->where('id', (string) $id)->delete();
            DB::table('identities')->whereIn('id', [(string) $previousIdentity, (string) $newIdentity])->delete();
            DB::table('accounts')->where('id', '00000000-0000-7000-8000-000000000009')->delete();
            DB::beginTransaction();
        }
    }

    public function testSaveOnlyPersistsAndFindsTypedPrincipal(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        $id = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $repository = new PrincipalRepository();
        $repository->save(new Principal($id, $identity, new AccountIdentifier('00000000-0000-7000-8000-000000000009')));
        $repository->save(new Principal($id, $identity, new AccountIdentifier('00000000-0000-7000-8000-000000000009')));
        self::assertSame((string) $identity, (string) $repository->findById($id)?->identityIdentifier());
        $this->assertDatabaseMissing('site_management_principal_group_memberships', ['principal_id' => (string) $id]);
        $repository->delete(new Principal($id, $identity, new AccountIdentifier('00000000-0000-7000-8000-000000000009')));
        self::assertNull($repository->findById($id));
    }
}
