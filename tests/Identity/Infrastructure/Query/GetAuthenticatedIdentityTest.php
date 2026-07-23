<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInput;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInterface;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateAccountPrincipalGroup;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetAuthenticatedIdentityTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsAuthenticatedIdentity(): void
    {
        $accountIdentifier = new AccountIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14d5aa');
        $principalGroupIdentifier = new PrincipalGroupIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14d5bb');
        $identityIdentifier = new IdentityIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14d5fe');
        CreateAccount::create((string) $accountIdentifier);
        CreateIdentity::create($identityIdentifier, [
            'identity_name' => 'test-user',
            'email' => 'test@example.com',
            'language' => 'ja',
            'profile_image' => 'profile/test.png',
        ]);
        CreateAccountPrincipalGroup::create($principalGroupIdentifier, $accountIdentifier);
        DB::table('account_principal_group_memberships')->insert([
            'id' => StrTestHelper::generateUuid(),
            'principal_group_id' => (string) $principalGroupIdentifier,
            'principal_id' => (string) $identityIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Redis::shouldReceive('get')->once()->andReturn(null);
        Redis::shouldReceive('setex')->once();

        $useCase = $this->app->make(GetAuthenticatedIdentityInterface::class);
        $readModel = $useCase->process(new GetAuthenticatedIdentityInput($identityIdentifier));

        $this->assertSame('019de7f3-78f3-7b55-9ed5-17f63e14d5fe', $readModel->identityIdentifier());
        $this->assertSame('test-user', $readModel->identityName());
        $this->assertSame('test@example.com', $readModel->email());
        $this->assertSame('ja', $readModel->language());
        $this->assertSame('http://127.0.0.1:8080/storage/profile/test.png', $readModel->profileImage());
        $this->assertSame('019de7f3-78f3-7b55-9ed5-17f63e14d5aa', $readModel->accountIdentifier());
        $this->assertSame('owner', $readModel->accountRole());
    }

    #[Group('useDb')]
    public function testProcessReturnsNullAccountIdentifierWhenIdentityDoesNotBelongToAccount(): void
    {
        $identityIdentifier = new IdentityIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14d5fe');
        CreateIdentity::create($identityIdentifier, [
            'identity_name' => 'test-user',
            'email' => 'test@example.com',
            'language' => 'ja',
            'profile_image' => null,
        ]);

        Redis::shouldReceive('get')->once()->andReturn(null);
        Redis::shouldReceive('setex')->never();

        $useCase = $this->app->make(GetAuthenticatedIdentityInterface::class);
        $readModel = $useCase->process(new GetAuthenticatedIdentityInput($identityIdentifier));

        $this->assertSame('019de7f3-78f3-7b55-9ed5-17f63e14d5fe', $readModel->identityIdentifier());
        $this->assertSame('test-user', $readModel->identityName());
        $this->assertSame('test@example.com', $readModel->email());
        $this->assertSame('ja', $readModel->language());
        $this->assertNull($readModel->profileImage());
        $this->assertNull($readModel->accountIdentifier());
        $this->assertNull($readModel->accountRole());
    }

    #[Group('useDb')]
    public function testProcessThrowsWhenIdentityDoesNotExist(): void
    {
        $useCase = $this->app->make(GetAuthenticatedIdentityInterface::class);

        $this->expectException(IdentityNotFoundException::class);

        $useCase->process(new GetAuthenticatedIdentityInput(
            new IdentityIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14d5ff'),
        ));
    }
}
