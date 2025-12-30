<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\PlainPassword;

class HashedPasswordTest extends TestCase
{
    /**
     * 正常系: Bcryptハッシュで正常にインスタンスが作成できること.
     *
     * @return void
     */
    public function testConstructWithBcryptHash(): void
    {
        $hash = '$2y$10$TI6VbqreLnmINEzgflyx0uJIgE3KhwXKWvayyQb3xkuMBkz2YqNc2';

        $hashedPassword = new HashedPassword($hash);

        $this->assertSame($hash, (string)$hashedPassword);
    }

    /**
     * 正常系: Argon2ハッシュで正常にインスタンスが作成できること.
     *
     * @return void
     */
    public function testConstructWithArgon2iHash(): void
    {
        $hash = '$argon2i$v=19$m=65536,t=4,p=1$V1RxeEVjNXpuaFN4R0tKTg$+J+NaG2/zSbZ6JdrMk4BTKdVfnlxG4LlAyIMwcp561A';

        $hashedPassword = new HashedPassword($hash);

        $this->assertSame($hash, (string)$hashedPassword);
    }

    /**
     * 正常系: Argon2idハッシュで正常にインスタンスが作成できること.
     *
     * @return void
     */
    public function testConstructWithArgon2idHash(): void
    {
        $hash = '$argon2id$v=19$m=65536,t=4,p=1$bUNCNWdVbkF3cWsweFRYNw$cpGbLWLDYf7DeVKtybv9c5lh26Dh/uVzFzha42hIliw';

        $hashedPassword = new HashedPassword($hash);

        $this->assertSame($hash, (string)$hashedPassword);
    }

    /**
     * 異常系: 空の場合、例外がスローされること.
     *
     * @return void
     */
    public function testConstructWithEmptyStringThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HashedPassword('');
    }

    /**
     * 異常系: 空白の場合、例外がスローされること.
     *
     * @return void
     */
    public function testConstructWithOnlySpaceThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HashedPassword('    ');
    }

    /**
     * 異常系: 不適切な値の場合、例外がスローされること.
     *
     * @return void
     */
    public function testConstructWithInvalidFormatThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HashedPassword('plain-password');
    }

    /**
     * 正常系: 平文からパスワードハッシュを作成できること.
     *
     * @return void
     */
    public function testFromPlainCreatesValidHash(): void
    {
        $plain = new PlainPassword('plain-password');

        $hashedPassword = HashedPassword::fromPlain($plain);

        $this->assertTrue(password_verify((string)$plain, (string)$hashedPassword));
    }
}
