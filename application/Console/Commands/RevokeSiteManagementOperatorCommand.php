<?php

declare(strict_types=1);

namespace Application\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Override;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementOperator\RevokeSiteManagementOperatorInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementOperator\RevokeSiteManagementOperatorInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementOperator\RevokeSiteManagementOperatorOutput;
use Throwable;

class RevokeSiteManagementOperatorCommand extends Command
{
    #[Override]
    protected $signature = 'site-management:operator:revoke {email : SiteManagement Operator権限を剥奪するAccountのメールアドレス}';

    #[Override]
    protected $description = 'AccountからSiteManagement Operator権限を剥奪する';

    public function handle(RevokeSiteManagementOperatorInterface $revokeSiteManagementOperator): int
    {
        try {
            $input = new RevokeSiteManagementOperatorInput(new Email((string) $this->argument('email')));
            $output = new RevokeSiteManagementOperatorOutput();
            DB::transaction(static fn () => $revokeSiteManagementOperator->process($input, $output));
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }

        $this->info('SiteManagement Operator権限を剥奪しました。');

        return self::SUCCESS;
    }
}
