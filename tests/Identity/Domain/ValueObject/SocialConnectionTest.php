<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProvider;

class SocialConnectionTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $provider = SocialProvider::GOOGLE;
        $providerUserId = 'google-user-1';

        $connection = new SocialConnection($provider, $providerUserId);

        $this->assertSame($provider, $connection->provider());
        $this->assertSame($providerUserId, $connection->providerUserId());
    }

    /**
     * 異常系: providerUserIdが空文字の場合、例外がスローされること.
     *
     * @return void
     */
    public function testEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SocialConnection(SocialProvider::LINE, '');
    }
}
