<?php

declare(strict_types=1);

namespace Application\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Override;
use Source\Account\Account\Application\UseCase\Command\RevokeOperations\RevokeOperationsInput;
use Source\Account\Account\Application\UseCase\Command\RevokeOperations\RevokeOperationsInterface;
use Source\Account\Account\Application\UseCase\Command\RevokeOperations\RevokeOperationsOutput;
use Source\Shared\Domain\ValueObject\Email;
use Throwable;

class RevokeOperationsCommand extends Command
{
    #[Override]
    protected $signature = 'operations:revoke {email : Operations権限を剥奪するAccountのメールアドレス}';

    #[Override]
    protected $description = 'Accountと同じメールアドレスのIdentityからOperations権限を剥奪する';

    public function handle(
        RevokeOperationsInterface $revokeOperations,
    ): int {
        try {
            $input = new RevokeOperationsInput(new Email((string) $this->argument('email')));
            $output = new RevokeOperationsOutput();
            DB::transaction(static fn () => $revokeOperations->process($input, $output));
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }

        $this->info('Operations権限を剥奪しました。');

        return self::SUCCESS;
    }
}
