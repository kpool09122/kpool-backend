<?php

declare(strict_types=1);

namespace Tests\Auth\Application\UseCase\Command\SocialLogin\Callback;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Auth\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackInput;
use Source\Auth\Domain\ValueObject\OAuthCode;
use Source\Auth\Domain\ValueObject\OAuthState;
use Source\Auth\Domain\ValueObject\SocialProvider;

class SocialLoginCallbackInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $provider = SocialProvider::LINE;
        $code = new OAuthCode('authorization-code');
        $state = new OAuthState('state-token', new DateTimeImmutable('+10 minutes'));

        $input = new SocialLoginCallbackInput($provider, $code, $state);

        $this->assertSame($provider, $input->provider());
        $this->assertSame($code, $input->code());
        $this->assertSame($state, $input->state());
    }
}
