<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Infrastructure\Repository;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Infrastructure\Repository\PrincipalRepository;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PrincipalRepositoryTest extends TestCase
{
    public function test__construct(): void
    {
        $repository = $this->app->make(PrincipalRepositoryInterface::class);

        $this->assertInstanceOf(PrincipalRepository::class, $repository);
    }

    #[Group('useDb')]
    public function testFindByIdsReturnsPrincipalsIndexedById(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);
        $principalIdentifierA = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principalIdentifierB = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $identityIdentifierA = new IdentityIdentifier(StrTestHelper::generateUuid());
        $identityIdentifierB = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifierA, ['email' => 'principal-a@example.com']);
        CreateIdentity::create($identityIdentifierB, ['email' => 'principal-b@example.com']);
        $this->createPrincipal($principalIdentifierA, $identityIdentifierA, $accountIdentifier);
        $this->createPrincipal($principalIdentifierB, $identityIdentifierB, $accountIdentifier);

        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $principals = $repository->findByIds([$principalIdentifierA, $principalIdentifierB]);

        $this->assertCount(2, $principals);
        $this->assertContainsOnlyInstancesOf(Principal::class, $principals);
        $this->assertArrayHasKey((string) $principalIdentifierA, $principals);
        $this->assertArrayHasKey((string) $principalIdentifierB, $principals);
        $this->assertSame((string) $identityIdentifierA, (string) $principals[(string) $principalIdentifierA]->identityIdentifier());
        $this->assertSame((string) $identityIdentifierB, (string) $principals[(string) $principalIdentifierB]->identityIdentifier());
    }

    #[Group('useDb')]
    public function testFindByIdsReturnsEmptyArrayWhenEmptyInput(): void
    {
        $repository = $this->app->make(PrincipalRepositoryInterface::class);

        $this->assertSame([], $repository->findByIds([]));
    }

    #[Group('useDb')]
    public function testFindByIdsExcludesMissingPrincipals(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);
        $existingPrincipalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $missingPrincipalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier, ['email' => 'principal-existing@example.com']);
        $this->createPrincipal($existingPrincipalIdentifier, $identityIdentifier, $accountIdentifier);

        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $principals = $repository->findByIds([$existingPrincipalIdentifier, $missingPrincipalIdentifier]);

        $this->assertCount(1, $principals);
        $this->assertArrayHasKey((string) $existingPrincipalIdentifier, $principals);
        $this->assertArrayNotHasKey((string) $missingPrincipalIdentifier, $principals);
    }

    private function createPrincipal(
        PrincipalIdentifier $principalIdentifier,
        IdentityIdentifier $identityIdentifier,
        AccountIdentifier $accountIdentifier,
    ): void {
        DB::table('account_principals')->insert([
            'id' => (string) $principalIdentifier,
            'identity_id' => (string) $identityIdentifier,
            'account_id' => (string) $accountIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
