<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Console;

use Application\Console\Commands\GrantWikiOperatorCommand;
use Application\Console\Commands\RevokeWikiOperatorCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Wiki\Principal\Application\UseCase\Command\GrantWikiOperator\GrantWikiOperatorInputPort;
use Source\Wiki\Principal\Application\UseCase\Command\GrantWikiOperator\GrantWikiOperatorInterface;
use Source\Wiki\Principal\Application\UseCase\Command\GrantWikiOperator\GrantWikiOperatorOutputPort;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator\RevokeWikiOperatorInputPort;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator\RevokeWikiOperatorInterface;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator\RevokeWikiOperatorOutputPort;
use Tests\Helper\CreateAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class WikiOperatorCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::registerCommand(new GrantWikiOperatorCommand());
        Artisan::registerCommand(new RevokeWikiOperatorCommand());
    }

    public function testOldAdministratorCommandsAreNotRegistered(): void
    {
        $commands = Artisan::all();
        $this->assertArrayNotHasKey('wiki:administrator:grant', $commands);
        $this->assertArrayNotHasKey('wiki:administrator:revoke', $commands);
    }

    /** @return array<string, array{string, class-string, string}> */
    public static function commands(): array
    {
        return [
            'grant' => ['wiki:operator:grant', GrantWikiOperatorInterface::class, '付与'],
            'revoke' => ['wiki:operator:revoke', RevokeWikiOperatorInterface::class, '剥奪'],
        ];
    }

    #[DataProvider('commands')]
    public function testReturnsSuccessAndPassesTypedEmailInsideTransaction(string $command, string $interface, string $verb): void
    {
        $useCase = Mockery::mock($interface);
        $level = DB::transactionLevel();
        $useCase->shouldReceive('process')->once()->withArgs(function (
            GrantWikiOperatorInputPort|RevokeWikiOperatorInputPort $input,
            GrantWikiOperatorOutputPort|RevokeWikiOperatorOutputPort $output,
        ) use ($level): bool {
            self::assertSame('operator@example.com', (string) $input->email());
            self::assertInstanceOf($input instanceof GrantWikiOperatorInputPort ? GrantWikiOperatorOutputPort::class : RevokeWikiOperatorOutputPort::class, $output);
            self::assertSame($level + 1, DB::transactionLevel());

            return true;
        });
        $this->app()->instance($interface, $useCase);

        $this->assertSame(0, Artisan::call($command, ['email' => 'operator@example.com']));
        $this->assertStringContainsString('Wiki Operator権限を' . $verb . 'しました。', Artisan::output());
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
