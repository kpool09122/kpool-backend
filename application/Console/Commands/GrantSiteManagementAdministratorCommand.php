<?php

declare(strict_types=1);

namespace Application\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Override;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementAdministrator\GrantSiteManagementAdministratorInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementAdministrator\GrantSiteManagementAdministratorInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementAdministrator\GrantSiteManagementAdministratorOutput;
use Throwable;

class GrantSiteManagementAdministratorCommand extends Command
{
    #[Override]
    protected $signature = 'site-management:administrator:grant {email : SiteManagement Administrator権限を付与するAccountのメールアドレス}';

    #[Override]
    protected $description = 'Operations権限を持つAccountにSiteManagement Administrator権限を付与する';

    public function handle(GrantSiteManagementAdministratorInterface $grantSiteManagementAdministrator): int
    {
        try {
            $input = new GrantSiteManagementAdministratorInput(new Email((string) $this->argument('email')));
            $output = new GrantSiteManagementAdministratorOutput();
            DB::transaction(static fn () => $grantSiteManagementAdministrator->process($input, $output));
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }

        $this->info('SiteManagement Administrator権限を付与しました。');

        return self::SUCCESS;
    }
}
