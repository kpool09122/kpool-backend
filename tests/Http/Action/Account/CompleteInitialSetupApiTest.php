<?php

declare(strict_types=1);

namespace Tests\Http\Action\Account;

use Application\Http\Middleware\EnsureAccountActive;
use Application\Http\Middleware\EnsureAuthenticated;
use Application\Http\Middleware\ResolveActorContext;
use Application\Models\Identity\Identity;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Account\Account\Application\UseCase\Command\CompleteInitialSetup\CompleteInitialSetupInputPort;
use Source\Account\Account\Application\UseCase\Command\CompleteInitialSetup\CompleteInitialSetupInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class CompleteInitialSetupApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $router = $this->app()['router'];
        $router->middlewareGroup('auth.api', [
            EnsureAuthenticated::class,
            EnsureAccountActive::class,
        ]);
        $router->aliasMiddleware('resolve.actor', ResolveActorContext::class);
        $router->aliasMiddleware('session', StartSession::class);

        Route::middleware(['api', 'session'])
            ->prefix('api/account')
            ->group(__DIR__ . '/../../../../routes/account_api.php');
    }

    public function testValidatesAccountType(): void
    {
        $this->authenticatePendingAccount();

        $this->postJson('/api/account/accounts/setup', ['accountType' => 'invalid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['accountType']);
    }

    public function testCompletesSetupAndRejectsRetryWithoutOverwritingType(): void
    {
        [$accountIdentifier] = $this->authenticatePendingAccount();

        $this->postJson('/api/account/accounts/setup', ['accountType' => 'corporation'])
            ->assertNoContent();
        $this->assertDatabaseHas('accounts', [
            'id' => (string) $accountIdentifier,
            'type' => 'corporation',
            'status' => 'active',
        ]);

        $this->postJson('/api/account/accounts/setup', ['accountType' => 'individual'])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'account_setup_unavailable');
        $this->assertDatabaseHas('accounts', [
            'id' => (string) $accountIdentifier,
            'type' => 'corporation',
            'status' => 'active',
        ]);
    }

    public function testDoesNotActivateSuspendedAccount(): void
    {
        [$accountIdentifier] = $this->authenticatePendingAccount(status: 'suspended', type: 'individual');

        $this->postJson('/api/account/accounts/setup', ['accountType' => 'corporation'])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'account_setup_unavailable');
        $this->assertDatabaseHas('accounts', [
            'id' => (string) $accountIdentifier,
            'type' => 'individual',
            'status' => 'suspended',
        ]);
    }

    public function testRollsBackWhenSetupUseCaseFails(): void
    {
        [$accountIdentifier] = $this->authenticatePendingAccount();
        /** @var CompleteInitialSetupInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(CompleteInitialSetupInterface::class);
        $useCase->shouldReceive('process')->once()->andReturnUsing(
            static function (CompleteInitialSetupInputPort $input): never {
                DB::table('accounts')->where('id', (string) $input->accountIdentifier())->update([
                    'type' => 'corporation',
                    'status' => 'active',
                ]);

                throw new RuntimeException('setup failed');
            },
        );
        $this->app()->instance(CompleteInitialSetupInterface::class, $useCase);

        $this->postJson('/api/account/accounts/setup', ['accountType' => 'corporation'])
            ->assertInternalServerError();
        $this->assertDatabaseHas('accounts', [
            'id' => (string) $accountIdentifier,
            'type' => null,
            'status' => 'pending',
        ]);
    }

    /** @return array{AccountIdentifier, IdentityIdentifier} */
    private function authenticatePendingAccount(
        string $status = 'pending',
        ?string $type = null,
    ): array {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier, [
            'type' => $type,
            'status' => $status,
        ]);
        CreateIdentity::create($identityIdentifier);
        DB::table('account_principals')->insert([
            'id' => StrTestHelper::generateUuid(),
            'identity_id' => (string) $identityIdentifier,
            'account_id' => (string) $accountIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $identity = Identity::query()->findOrFail((string) $identityIdentifier);
        Auth::shouldReceive('check')->byDefault()->andReturn(true);
        Auth::shouldReceive('id')->byDefault()->andReturn((string) $identityIdentifier);
        Auth::shouldReceive('user')->byDefault()->andReturn($identity);
        /** @var AuthServiceInterface&Mockery\MockInterface $authService */
        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('isCurrentSessionValid')->byDefault()->andReturn(true);
        $authService->shouldNotReceive('logout');
        $this->app()->instance(AuthServiceInterface::class, $authService);

        return [$accountIdentifier, $identityIdentifier];
    }
}
