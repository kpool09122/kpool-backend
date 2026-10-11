<?php

declare(strict_types=1);

namespace Application\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Override;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementAdministrator\RevokeSiteManagementAdministratorInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementAdministrator\RevokeSiteManagementAdministratorInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementAdministrator\RevokeSiteManagementAdministratorOutput;
use Throwable;

class RevokeSiteManagementAdministratorCommand extends Command
{
    #[Override]
    protected $signature = 'site-management:administrator:revoke {email : SiteManagement Administrator権限を剥奪するAccountのメールアドレス}';

    #[Override]
    protected $description = 'AccountからSiteManagement Administrator権限を剥奪する';

    public function handle(RevokeSiteManagementAdministratorInterface $revokeSiteManagementAdministrator): int
    {
        try {
            $input = new RevokeSiteManagementAdministratorInput(new Email((string) $this->argument('email')));
            $output = new RevokeSiteManagementAdministratorOutput();
            DB::transaction(static fn () => $revokeSiteManagementAdministrator->process($input, $output));
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }

        $this->info('SiteManagement Administrator権限を剥奪しました。');

        return self::SUCCESS;
    }
}
