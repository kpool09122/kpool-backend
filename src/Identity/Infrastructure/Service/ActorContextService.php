<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Http\Context\AuthContextCache;
use Illuminate\Support\Facades\DB;
use Source\Identity\Application\Service\ActorContextServiceInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class ActorContextService implements ActorContextServiceInterface
{
    public function __construct(private AuthContextCache $authContextCache)
    {
    }

    public function forget(IdentityIdentifier $identityIdentifier): void
    {
        $forget = function () use ($identityIdentifier): void {
            $this->authContextCache->forgetActor($identityIdentifier);
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($forget);

            return;
        }

        $forget();
    }
}
