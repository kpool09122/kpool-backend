<?php

declare(strict_types=1);

namespace Source\Analyze\App\Infrastructure\Service;

use Application\Models\Identity\ArchivedIdentity;
use Application\Models\Identity\Identity;
use DateTimeImmutable;
use Source\Analyze\App\Application\Service\RegisteredIdentityCountQueryServiceInterface;

readonly class RegisteredIdentityCountQueryService implements RegisteredIdentityCountQueryServiceInterface
{
    public function countBefore(DateTimeImmutable $endExclusive): string
    {
        $active = Identity::query()->where('created_at', '<', $endExclusive)->count();
        $subsequentlyWithdrawn = ArchivedIdentity::query()->where('identity_created_at', '<', $endExclusive)->where('archived_at', '>=', $endExclusive)->count();

        return (string) ($active + $subsequentlyWithdrawn);
    }
}
