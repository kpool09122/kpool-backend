<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\VerifySocialLinkingEmail;

use Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail\VerifySocialLinkingEmailOutput;
use Source\Shared\Application\Exception\OutputNotInitializedException;
use Tests\TestCase;

class VerifySocialLinkingEmailOutputTest extends TestCase
{
    public function testOutput(): void
    {
        $output = new VerifySocialLinkingEmailOutput();
        $output->setRedirectUrl('/mypage');
        $this->assertSame('/mypage', $output->redirectUrl());
        $this->assertSame(['redirectUrl' => '/mypage'], $output->toArray());
    }

    public function testUnsetOutputFails(): void
    {
        $this->expectException(OutputNotInitializedException::class);
        (new VerifySocialLinkingEmailOutput())->toArray();
    }
}
