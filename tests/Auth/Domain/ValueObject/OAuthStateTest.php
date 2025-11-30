<?php

declare(strict_types=1);

namespace Tests\Auth\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Auth\Domain\ValueObject\OAuthState;

class OAuthStateTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $stateToken = 'state-token';
        $expiresAt = new DateTimeImmutable('+10 minutes');

        $state = new OAuthState($stateToken, $expiresAt);

        $this->assertSame($stateToken, (string)$state);
        $this->assertSame($expiresAt, $state->expiresAt());
    }

    /**
     * 異常系: トークンが空の場合、例外がスローされること.
     *
     * @return void
     */
    public function testThrowsExceptionWhenStateIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OAuthState('', new DateTimeImmutable('+5 minutes'));
    }

    /**
     * 異常系: トークンが最大長を超える場合、例外がスローされること.
     *
     * @return void
     */
    public function testThrowsExceptionWhenStateIsTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OAuthState(str_repeat('a', OAuthState::MAX_LENGTH + 1), new DateTimeImmutable('+5 minutes'));
    }
}
