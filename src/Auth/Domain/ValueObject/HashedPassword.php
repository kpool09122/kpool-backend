<?php

declare(strict_types=1);

namespace Source\Auth\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class HashedPassword extends StringBaseValue
{
    public function __construct(
        protected string $id,
    ) {
        parent::__construct($id);
        $this->validate($id);
    }

    protected function validate(
        string $value,
    ): void {

        if ($value === '') {
            throw new InvalidArgumentException('パスワードは空にできません。');
        }

        // bcrypt または argon2 形式のハッシュかを検証
        if (! $this->isValidHash($value)) {
            throw new InvalidArgumentException('パスワードは有効な bcrypt または argon2 形式のハッシュでなければなりません。');
        }
    }

    /**
     * 平文パスワードからハッシュ化されたパスワードを生成
     *
     * @param PlainPassword $plainPassword
     *
     * @return self
     *
     * @throws InvalidArgumentException
     */
    public static function fromPlain(PlainPassword $plainPassword): self
    {
        $hashed = password_hash((string)$plainPassword, \PASSWORD_DEFAULT);

        if (! $hashed) {
            throw new InvalidArgumentException('パスワードのハッシュ化に失敗しました。');
        }

        return new self($hashed);
    }

    /**
     * ハッシュが有効な形式かを検証
     *
     * 注意: ここでは基本的なフォーマットチェックのみを行う。
     * 実際のハッシュの妥当性検証は password_verify() で行われる。
     * VOの責務としては、明らかに不正な値（空文字や平文など）を
     * インスタンス化時に排除することに限定する。
     *
     * @param string $value
     *
     * @return bool
     */
    private function isValidHash(string $value): bool
    {
        // bcrypt形式: $2y$10$... または $2a$10$... (60文字)
        if (preg_match('/^\$2[ay]\$\d{2}\$.{53}$/', $value) === 1) {
            return true;
        }

        // argon2i形式（基本的なプレフィックスのみチェック）
        if (str_starts_with($value, '$argon2i$')) {
            return true;
        }

        // argon2id形式（基本的なプレフィックスのみチェック）
        if (str_starts_with($value, '$argon2id$')) {
            return true;
        }

        return false;
    }
}
