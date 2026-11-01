<?php

declare(strict_types=1);

namespace Application\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Override;
use Source\Shared\Domain\ValueObject\Email;
use Source\Wiki\Grading\Application\UseCase\Command\ChangeCollaboratorRole\ChangeCollaboratorRoleInput;
use Source\Wiki\Grading\Application\UseCase\Command\ChangeCollaboratorRole\ChangeCollaboratorRoleInterface;
use Source\Wiki\Grading\Application\UseCase\Command\ChangeCollaboratorRole\ChangeCollaboratorRoleOutput;
use Source\Wiki\Grading\Domain\ValueObject\CollaboratorRole;
use Throwable;

class DemoteWikiCollaboratorCommand extends Command
{
    #[Override]
    protected $signature = 'wiki:collaborator:demote {email : 対象AccountおよびIdentityのメールアドレス}';

    #[Override]
    protected $description = '指定メールアドレスのWikiユーザーを降格する';

    public function handle(ChangeCollaboratorRoleInterface $changeCollaboratorRole): int
    {
        try {
            $input = new ChangeCollaboratorRoleInput(new Email((string) $this->argument('email')), CollaboratorRole::COLLABORATOR);
            $output = new ChangeCollaboratorRoleOutput();
            DB::transaction(static fn () => $changeCollaboratorRole->process($input, $output));
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }
        $this->info($output->changed() ? 'Wikiユーザーを降格しました。' : '既に対象ロールのため変更はありません。');

        return self::SUCCESS;
    }
}
