<?php

declare(strict_types=1);

namespace Application\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Override;
use Source\Shared\Domain\ValueObject\Email;
use Source\Wiki\Principal\Application\UseCase\Command\GrantWikiOperator\GrantWikiOperatorInput;
use Source\Wiki\Principal\Application\UseCase\Command\GrantWikiOperator\GrantWikiOperatorInterface;
use Source\Wiki\Principal\Application\UseCase\Command\GrantWikiOperator\GrantWikiOperatorOutput;
use Throwable;

class GrantWikiOperatorCommand extends Command
{
    #[Override]
    protected $signature = 'wiki:operator:grant {email : Wiki Operator権限を付与するAccountのメールアドレス}';

    #[Override]
    protected $description = 'Operations権限を持つAccountにWiki Operator権限を付与する';

    public function handle(GrantWikiOperatorInterface $grantWikiOperator): int
    {
        try {
            $input = new GrantWikiOperatorInput(new Email((string) $this->argument('email')));
            $output = new GrantWikiOperatorOutput();
            DB::transaction(static fn () => $grantWikiOperator->process($input, $output));
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }

        $this->info('Wiki Operator権限を付与しました。');

        return self::SUCCESS;
    }
}
