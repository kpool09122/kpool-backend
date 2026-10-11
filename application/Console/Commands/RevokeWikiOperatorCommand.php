<?php

declare(strict_types=1);

namespace Application\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Override;
use Source\Shared\Domain\ValueObject\Email;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator\RevokeWikiOperatorInput;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator\RevokeWikiOperatorInterface;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator\RevokeWikiOperatorOutput;
use Throwable;

class RevokeWikiOperatorCommand extends Command
{
    #[Override]
    protected $signature = 'wiki:operator:revoke {email : Wiki Operator権限を剥奪するAccountのメールアドレス}';

    #[Override]
    protected $description = 'AccountからWiki Operator権限を剥奪する';

    public function handle(RevokeWikiOperatorInterface $revokeWikiOperator): int
    {
        try {
            $input = new RevokeWikiOperatorInput(new Email((string) $this->argument('email')));
            $output = new RevokeWikiOperatorOutput();
            DB::transaction(static fn () => $revokeWikiOperator->process($input, $output));
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }

        $this->info('Wiki Operator権限を剥奪しました。');

        return self::SUCCESS;
    }
}
