<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Service;

use Application\Http\Context\AuthContextCache;
use Illuminate\Support\Facades\DB;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\Service\WikiContextServiceInterface;

readonly class WikiContextService implements WikiContextServiceInterface
{
    public function __construct(private AuthContextCache $authContextCache)
    {
    }

    public function forget(IdentityIdentifier $identityIdentifier): void
    {
        $forget = fn () => $this->authContextCache->forgetWiki($identityIdentifier);
        if (DB::transactionLevel() > 0) {
            DB::afterCommit($forget);

            return;
        }
        $forget();
    }
}
