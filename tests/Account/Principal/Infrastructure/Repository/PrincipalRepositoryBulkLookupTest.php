<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Infrastructure\Repository;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\SiteManagementPrincipalGroupsFixture;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class PrincipalRepositoryBulkLookupTest extends TestCase
{
    public function testBulkLookupsRespectAccountsIdentitiesAndStatuses(): void
    {
        $f = new SiteManagementPrincipalGroupsFixture();
        $repository = $this->app()->make(PrincipalRepositoryInterface::class);
        $related = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $f->administrator->identityIdentifier(), $f->otherAccount, PrincipalStatus::SUSPENDED);
        $repository->save($related);
        $principals = $repository->findByAccountIds([$f->account]);
        self::assertCount(3, $principals);
        foreach ($principals as $principal) {
            self::assertSame((string) $f->account, (string) $principal->accountIdentifier());
        }
        self::assertCount(5, $repository->findByAccountIds([$f->account, $f->otherAccount]));
        $principals = $repository->findAllByIdentityIdentifiers([$f->administrator->identityIdentifier(), $f->foreign->identityIdentifier()]);
        self::assertCount(3, $principals);
        $principalsById = [];
        foreach ($principals as $principal) {
            $principalsById[(string) $principal->principalIdentifier()] = $principal;
        }
        self::assertSame(PrincipalStatus::SUSPENDED, $principalsById[(string) $related->principalIdentifier()]->status());
        DB::enableQueryLog();

        try {
            DB::flushQueryLog();
            self::assertSame([], $repository->findByAccountIds([]));
            self::assertSame([], $repository->findAllByIdentityIdentifiers([]));
            self::assertSame([], DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }
}
