<?php

declare(strict_types=1);

namespace Application\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Override;
use Source\Account\Account\Application\UseCase\Command\GrantOperations\GrantOperationsInput;
use Source\Account\Account\Application\UseCase\Command\GrantOperations\GrantOperationsInterface;
use Source\Account\Account\Application\UseCase\Command\GrantOperations\GrantOperationsOutput;
use Source\Shared\Domain\ValueObject\Email;
use Throwable;

class GrantOperationsCommand extends Command
{
    #[Override]
    protected $signature = 'operations:grant {email : Operations権限を付与するAccountのメールアドレス}';

    #[Override]
    protected $description = 'AccountにOperations権限を付与する';

    public function handle(
        GrantOperationsInterface $grantOperations,
    ): int {
        try {
            $input = new GrantOperationsInput(new Email((string) $this->argument('email')));
            $output = new GrantOperationsOutput();
            DB::transaction(static fn () => $grantOperations->process($input, $output));
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }

        $this->info('Operations権限を付与しました。');

        return self::SUCCESS;
    }
}
