<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Service;

use Application\Http\Context\AuthContextCache;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Infrastructure\Service\AccountContextInvalidationService;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountContextInvalidationServiceTest extends TestCase
{
    #[Group('useDb')]
    public function testForgetByAccountIdentifierForgetsTargetAccountPrincipalContexts(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $otherAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $otherIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $this->insertAccount((string) $accountIdentifier, 'target@example.com');
        $this->insertAccount((string) $otherAccountIdentifier, 'other@example.com');
        $this->insertIdentity((string) $identityIdentifier, 'target-user@example.com');
        $this->insertIdentity((string) $otherIdentityIdentifier, 'other-user@example.com');
        $this->insertPrincipal((string) $accountIdentifier, (string) $identityIdentifier);
        $this->insertPrincipal((string) $otherAccountIdentifier, (string) $otherIdentityIdentifier);

        /** @var AuthContextCache&Mockery\MockInterface $cache */
        $cache = Mockery::mock(AuthContextCache::class);
        $cache->shouldReceive('forgetAccount')
            ->once()
            ->with(Mockery::on(static fn (IdentityIdentifier $actual): bool => (string) $actual === (string) $identityIdentifier));

        (new AccountContextInvalidationService($cache))->forgetByAccountIdentifier($accountIdentifier);
    }

    private function insertAccount(string $accountIdentifier, string $email): void
    {
        DB::table('accounts')->insert([
            'id' => $accountIdentifier,
            'email' => $email,
            'type' => AccountType::CORPORATION->value,
            'name' => 'Test Account',
            'status' => 'active',
            'category' => AccountCategory::GENERAL->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertIdentity(string $identityIdentifier, string $email): void
    {
        DB::table('identities')->insert([
            'id' => $identityIdentifier,
            'identity_name' => substr(str_replace('-', '', $identityIdentifier), 0, 32),
            'email' => $email,
            'language' => 'ja',
            'password' => 'password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertPrincipal(string $accountIdentifier, string $identityIdentifier): void
    {
        DB::table('account_principals')->insert([
            'id' => StrTestHelper::generateUuid(),
            'identity_id' => $identityIdentifier,
            'account_id' => $accountIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
