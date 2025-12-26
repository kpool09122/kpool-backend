<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\SocialLogin\Redirect;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\SocialLogin\Redirect\SocialLoginRedirectOutput;

class SocialLoginRedirectOutputTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $redirectUrl = 'https://example.com/auth/redirect';

        $output = new SocialLoginRedirectOutput();

        $this->assertNull($output->redirectUrl());

        $output->setRedirectUrl($redirectUrl);

        $this->assertSame($redirectUrl, $output->redirectUrl());
    }
}
