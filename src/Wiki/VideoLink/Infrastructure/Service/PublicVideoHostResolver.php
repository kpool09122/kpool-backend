<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Service;

use Closure;
use Source\Wiki\VideoLink\Application\Exception\VideoMetadataUnavailableException;

final readonly class PublicVideoHostResolver implements PublicVideoHostResolverInterface
{
    /** @var Closure(string): (array<array<array-key, mixed>>|false) */
    private Closure $lookup;

    /** @param (Closure(string): (array<array<array-key, mixed>>|false))|null $lookup */
    public function __construct(?Closure $lookup = null)
    {
        $this->lookup = $lookup ?? static fn (string $host): array|false => dns_get_record($host, DNS_A);
    }

    public function resolve(string $host): string
    {
        $records = ($this->lookup)($host);
        if ($records === false || $records === []) {
            throw new VideoMetadataUnavailableException();
        }
        foreach ($records as $record) {
            $ip = $record['ip'] ?? null;
            if (is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_GLOBAL_RANGE) !== false) {
                return $ip;
            }
        }

        throw new VideoMetadataUnavailableException();
    }
}
