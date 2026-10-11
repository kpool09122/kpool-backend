<?php

declare(strict_types=1);

namespace Application\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Override;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementOperator\GrantSiteManagementOperatorInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementOperator\GrantSiteManagementOperatorInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementOperator\GrantSiteManagementOperatorOutput;
use Throwable;

class GrantSiteManagementOperatorCommand extends Command
{
    #[Override]
    protected $signature = 'site-management:operator:grant {email : SiteManagement Operator権限を付与するAccountのメールアドレス}';

    #[Override]
    protected $description = 'Operations権限を持つAccountにSiteManagement Operator権限を付与する';

    public function handle(GrantSiteManagementOperatorInterface $grantSiteManagementOperator): int
    {
        try {
            $input = new GrantSiteManagementOperatorInput(new Email((string) $this->argument('email')));
            $output = new GrantSiteManagementOperatorOutput();
            DB::transaction(static fn () => $grantSiteManagementOperator->process($input, $output));
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }

        $this->info('SiteManagement Operator権限を付与しました。');

        return self::SUCCESS;
    }
}
