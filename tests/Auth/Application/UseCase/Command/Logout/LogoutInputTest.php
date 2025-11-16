<?php

declare(strict_types=1);

namespace Tests\Auth\Application\UseCase\Command\Logout;

use PHPUnit\Framework\TestCase;
use Source\Auth\Application\UseCase\Command\Logout\LogoutInput;

class LogoutInputTest extends TestCase
{
    /**
     * 正常系： 正しくインスタンスがを作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $input = new LogoutInput();
        $this->assertInstanceOf(LogoutInput::class, $input);
    }
}
