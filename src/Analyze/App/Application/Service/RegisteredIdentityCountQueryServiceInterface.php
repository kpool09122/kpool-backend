<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\Service;

use DateTimeImmutable;

interface RegisteredIdentityCountQueryServiceInterface
{
    public function countBefore(DateTimeImmutable $endExclusive): string;

    /** Registration-time access country, including later-withdrawn identities at historical cutoffs.
     * Missing registration country belongs to unknown, independent of account memberships/addresses.
     * @return array<string, string> Country code (empty for unknown) => distinct Identity count.
     */
    public function countByCountryBefore(DateTimeImmutable $endExclusive): array;
}
