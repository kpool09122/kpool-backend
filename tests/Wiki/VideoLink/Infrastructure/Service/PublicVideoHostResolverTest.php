<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\VideoLink\Application\Exception\VideoMetadataUnavailableException;
use Source\Wiki\VideoLink\Infrastructure\Service\PublicVideoHostResolver;

class PublicVideoHostResolverTest extends TestCase
{
    public function testPinsOnlyGloballyRoutableIpv4(): void
    {
        $resolver = new PublicVideoHostResolver(static fn (string $host): array => [['ip' => '127.0.0.1'], ['ip' => '8.8.8.8']]);
        self::assertSame('8.8.8.8', $resolver->resolve('www.tiktok.com'));
    }

    #[DataProvider('unsafeAddresses')]
    public function testRejectsInternalReservedAndMissingAddresses(string $ip): void
    {
        $resolver = new PublicVideoHostResolver(static fn (string $host): array => [['ip' => $ip]]);
        $this->expectException(VideoMetadataUnavailableException::class);
        $resolver->resolve('www.tiktok.com');
    }

    /** @return list<array{string}> */
    public static function unsafeAddresses(): array
    {
        return array_map(static fn (string $ip): array => [$ip], ['127.0.0.1', '10.0.0.1', '169.254.169.254', '172.16.0.1', '192.168.1.1', '100.64.0.1', '198.18.0.1', '0.0.0.0', '::1', 'invalid']);
    }
}
