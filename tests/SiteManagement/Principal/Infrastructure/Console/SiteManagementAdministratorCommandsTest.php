<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Infrastructure\Console;

use Application\Console\Commands\GrantSiteManagementAdministratorCommand;
use Application\Console\Commands\RevokeSiteManagementAdministratorCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementAdministrator\GrantSiteManagementAdministratorInputPort;
use Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementAdministrator\GrantSiteManagementAdministratorInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementAdministrator\GrantSiteManagementAdministratorOutputPort;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementAdministrator\RevokeSiteManagementAdministratorInputPort;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementAdministrator\RevokeSiteManagementAdministratorInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementAdministrator\RevokeSiteManagementAdministratorOutputPort;
use Tests\Helper\CreateAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class SiteManagementAdministratorCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::registerCommand(new GrantSiteManagementAdministratorCommand());
        Artisan::registerCommand(new RevokeSiteManagementAdministratorCommand());
    }

    /** @return array<string, array{string, class-string, string}> */
    public static function commands(): array
    {
        return [
            'grant' => ['site-management:administrator:grant', GrantSiteManagementAdministratorInterface::class, '付与'],
            'revoke' => ['site-management:administrator:revoke', RevokeSiteManagementAdministratorInterface::class, '剥奪'],
        ];
    }

    #[DataProvider('commands')]
    public function testReturnsSuccessAndPassesTypedEmailInsideTransaction(string $command, string $interface, string $verb): void
    {
        $useCase = Mockery::mock($interface);
        $level = DB::transactionLevel();
        $useCase->shouldReceive('process')->once()->withArgs(function (
            GrantSiteManagementAdministratorInputPort|RevokeSiteManagementAdministratorInputPort $input,
            GrantSiteManagementAdministratorOutputPort|RevokeSiteManagementAdministratorOutputPort $output,
        ) use ($level): bool {
            self::assertSame('operator@example.com', (string) $input->email());
            self::assertInstanceOf($input instanceof GrantSiteManagementAdministratorInputPort ? GrantSiteManagementAdministratorOutputPort::class : RevokeSiteManagementAdministratorOutputPort::class, $output);
            self::assertSame($level + 1, DB::transactionLevel());

            return true;
        });
        $this->app()->instance($interface, $useCase);

        $this->assertSame(0, Artisan::call($command, ['email' => 'operator@example.com']));
        $this->assertStringContainsString('SiteManagement Administrator権限を' . $verb . 'しました。', Artisan::output());
        $this->assertSame($level, DB::transactionLevel());
    }

    #[DataProvider('commands')]
    public function testRejectsInvalidEmailWithoutCallingUseCase(string $command, string $interface, string $verb): void
    {
        $useCase = Mockery::mock($interface);
        $useCase->shouldNotReceive('process');
        $this->app()->instance($interface, $useCase);

        $this->assertSame(1, Artisan::call($command, ['email' => 'not-an-email']));
        $this->assertNotSame('', trim(Artisan::output()));
    }

    #[DataProvider('commands')]
    public function testRollsBackPartialWritesAndReturnsFailureMessage(string $command, string $interface, string $verb): void
    {
        $id = StrTestHelper::generateUuid();
        $level = DB::transactionLevel();
        $useCase = Mockery::mock($interface);
        $useCase->shouldReceive('process')->once()->andReturnUsing(static function () use ($id): void {
            CreateAccount::create($id);

            throw new RuntimeException('operation failed');
        });
        $this->app()->instance($interface, $useCase);

        $this->assertSame(1, Artisan::call($command, ['email' => 'operator@example.com']));
        $this->assertStringContainsString('operation failed', Artisan::output());
        $this->assertDatabaseMissing('accounts', ['id' => $id]);
        $this->assertSame($level, DB::transactionLevel());
    }
}
