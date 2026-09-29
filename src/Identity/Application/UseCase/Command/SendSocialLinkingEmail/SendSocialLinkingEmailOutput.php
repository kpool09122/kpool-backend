<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SendSocialLinkingEmail;

use Source\Shared\Application\Exception\OutputNotInitializedException;

class SendSocialLinkingEmailOutput implements SendSocialLinkingEmailOutputPort
{
    private ?bool $accepted = null;

    public function setAccepted(bool $accepted): void
    {
        $this->accepted = $accepted;
    }

    public function accepted(): bool
    {
        return $this->accepted ?? throw new OutputNotInitializedException('Output is not set.');
    }

    /** @return array{accepted: bool} */
    public function toArray(): array
    {
        return ['accepted' => $this->accepted()];
    }
}
