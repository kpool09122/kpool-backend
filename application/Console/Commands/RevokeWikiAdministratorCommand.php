<?php

declare(strict_types=1);

namespace Application\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Override;
use Source\Shared\Domain\ValueObject\Email;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiAdministrator\RevokeWikiAdministratorInput;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiAdministrator\RevokeWikiAdministratorInterface;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiAdministrator\RevokeWikiAdministratorOutput;
use Throwable;

class RevokeWikiAdministratorCommand extends Command
{
    #[Override]
    protected $signature = 'wiki:administrator:revoke {email : Wiki Administrator権限を剥奪するAccountのメールアドレス}';

    #[Override]
    protected $description = 'IdentityからWiki Administrator権限を剥奪する';

    public function handle(RevokeWikiAdministratorInterface $revokeWikiAdministrator): int
    {
        try {
            $input = new RevokeWikiAdministratorInput(new Email((string) $this->argument('email')));
            $output = new RevokeWikiAdministratorOutput();
            DB::transaction(static fn () => $revokeWikiAdministrator->process($input, $output));
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }

        $this->info('Wiki Administrator権限を剥奪しました。');

        return self::SUCCESS;
    }
}
