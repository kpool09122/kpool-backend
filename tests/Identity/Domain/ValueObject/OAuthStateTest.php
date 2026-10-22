<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\OAuthState;

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

        $state = new OAuthState($stateToken);

        $this->assertSame($stateToken, (string)$state);
    }

    /**
     * 異常系: トークンが空の場合、例外がスローされること.
     *
     * @return void
     */
    public function testThrowsExceptionWhenStateIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OAuthState('');
    }

    /**
     * 異常系: トークンが最大長を超える場合、例外がスローされること.
     *
     * @return void
     */
    public function testThrowsExceptionWhenStateIsTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OAuthState(str_repeat('a', OAuthState::MAX_LENGTH + 1));
    }
}
