<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail;

use Source\Shared\Application\Exception\OutputNotInitializedException;

class VerifySocialLinkingEmailOutput implements VerifySocialLinkingEmailOutputPort
{
    private ?string $redirectUrl = null;

    public function setRedirectUrl(string $redirectUrl): void
    {
        $this->redirectUrl = $redirectUrl;
    }

    public function redirectUrl(): string
    {
        return $this->redirectUrl ?? throw new OutputNotInitializedException('Output is not set.');
    }

    /** @return array{redirectUrl: string} */
    public function toArray(): array
    {
        return ['redirectUrl' => $this->redirectUrl()];
    }
}
