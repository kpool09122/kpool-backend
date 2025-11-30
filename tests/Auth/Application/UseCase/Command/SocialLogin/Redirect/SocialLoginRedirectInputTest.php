<?php

declare(strict_types=1);

namespace Tests\Auth\Application\UseCase\Command\SocialLogin\Redirect;

use PHPUnit\Framework\TestCase;
use Source\Auth\Application\UseCase\Command\SocialLogin\Redirect\SocialLoginRedirectInput;
use Source\Auth\Domain\ValueObject\SocialProvider;

class SocialLoginRedirectInputTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $provider = SocialProvider::GOOGLE;

        $input = new SocialLoginRedirectInput($provider);

        $this->assertSame($provider, $input->provider());
    }
}
