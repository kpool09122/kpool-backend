<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\SocialLogin\Callback;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackOutput;

class SocialLoginCallbackOutputTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $redirectUrl = '/auth/callback';

        $output = new SocialLoginCallbackOutput();

        $this->assertNull($output->redirectUrl());

        $output->setRedirectUrl($redirectUrl);

        $this->assertSame($redirectUrl, $output->redirectUrl());
    }
}
