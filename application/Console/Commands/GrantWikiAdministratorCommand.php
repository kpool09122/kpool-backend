<?php

declare(strict_types=1);

namespace Application\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Override;
use Source\Shared\Domain\ValueObject\Email;
use Source\Wiki\Principal\Application\UseCase\Command\GrantWikiAdministrator\GrantWikiAdministratorInput;
use Source\Wiki\Principal\Application\UseCase\Command\GrantWikiAdministrator\GrantWikiAdministratorInterface;
use Source\Wiki\Principal\Application\UseCase\Command\GrantWikiAdministrator\GrantWikiAdministratorOutput;
use Throwable;

class GrantWikiAdministratorCommand extends Command
{
    #[Override]
    protected $signature = 'wiki:administrator:grant {email : Wiki Administrator権限を付与するAccountのメールアドレス}';

    #[Override]
    protected $description = 'Operations権限を持つAccountにWiki Administrator権限を付与する';

    public function handle(GrantWikiAdministratorInterface $grantWikiAdministrator): int
    {
        try {
            $input = new GrantWikiAdministratorInput(new Email((string) $this->argument('email')));
            $output = new GrantWikiAdministratorOutput();
            DB::transaction(static fn () => $grantWikiAdministrator->process($input, $output));
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }

        $this->info('Wiki Administrator権限を付与しました。');

        return self::SUCCESS;
    }
}
