<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal;

use Database\Seeders\AccountAuthorizationSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SiteManagementAuthorizationSeeder;
use Database\Seeders\TestAccountSeeder;
use Database\Seeders\WikiEditorSampleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInput;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInterface;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountOutput;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Principal\Domain\Event\PrincipalCreated;
use Source\Identity\Domain\Event\IdentityWithdrawing;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Application\EventHandler\IdentityWithdrawingHandler;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class PrincipalLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app()->make(AccountAuthorizationSeeder::class)->run();
    }

    public function testRegistrationSynchronouslyProvisionsGeneralAndWithdrawalDeletesMemberships(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = new Email((string) $identity.'@example.com');
        CreateIdentity::create($identity, ['email' => (string) $email]);
        DB::transaction(function () use ($identity, $email): void {
            $this->app()->make(CreateAccountInterface::class)->process(new CreateAccountInput($email, new AccountName('Registration'), $identity), new CreateAccountOutput());
            $this->assertDatabaseHas('site_management_principals', ['identity_id' => (string) $identity]);
        });
        $id = DB::table('site_management_principals')->where('identity_id', (string) $identity)->value('id');
        $this->assertSame(1, DB::table('site_management_principal_group_memberships')->where('principal_id', $id)->count());
        $this->app()->make(IdentityWithdrawingHandler::class)->handle(new IdentityWithdrawing($identity));
        $this->assertDatabaseMissing('site_management_principals', ['id' => $id]);
        $this->assertDatabaseMissing('site_management_principal_group_memberships', ['principal_id' => $id]);
        $this->assertDatabaseCount('site_management_roles', 2);
        $this->assertDatabaseCount('site_management_policies', 2);
    }

    public function testRegistrationListenerFailureRollsBackBothPrincipalsAndAccount(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = new Email((string) $identity.'@example.com');
        CreateIdentity::create($identity, ['email' => (string) $email]);
        Event::listen(PrincipalCreated::class, function () use ($identity): void {
            $this->assertDatabaseHas('site_management_principals', ['identity_id' => (string) $identity]);

            throw new RuntimeException('registration failed');
        });

        try {
            DB::transaction(fn () => $this->app()->make(CreateAccountInterface::class)->process(new CreateAccountInput($email, new AccountName('Rollback'), $identity), new CreateAccountOutput()));
            $this->fail('Expected listener failure');
        } catch (RuntimeException $exception) {
            $this->assertSame('registration failed', $exception->getMessage());
        }
        $this->assertDatabaseMissing('accounts', ['email' => (string) $email]);
        $this->assertDatabaseMissing('account_principals', ['identity_id' => (string) $identity]);
        $this->assertDatabaseMissing('site_management_principals', ['identity_id' => (string) $identity]);
    }

    public function testSeedersProvisionEveryIdentityAsGeneralAndAreIdempotent(): void
    {
        $seeder = $this->app()->make(DatabaseSeeder::class);
        $seeder->setContainer($this->app());
        $seeder->run();
        $first = DB::table('site_management_principals')->orderBy('identity_id')->pluck('id', 'identity_id')->all();
        $this->app()->make(SiteManagementAuthorizationSeeder::class)->run();
        $this->app()->make(TestAccountSeeder::class)->run();
        $this->app()->make(WikiEditorSampleSeeder::class)->run();
        $this->assertSame($first, DB::table('site_management_principals')->orderBy('identity_id')->pluck('id', 'identity_id')->all());
        $this->assertSame(DB::table('identities')->count(), count($first));
        $this->assertSame(count($first), DB::table('site_management_principal_group_memberships')->where('principal_group_id', SiteManagementAuthorizationSeeder::GENERAL_GROUP)->count());
        $this->assertSame(0, DB::table('site_management_principal_group_memberships')->where('principal_group_id', SiteManagementAuthorizationSeeder::ADMIN_GROUP)->count());
    }
}
