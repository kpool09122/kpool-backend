<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\ApproveAuthorization;

class ApproveAuthorizationOutput implements ApproveAuthorizationOutputPort
{
    private string $redirectUri = "";

    public function setRedirectUri(string $redirectUri): void
    {
        $this->redirectUri = $redirectUri;
    }

    /** @return array{redirectUri: string} */
    public function toArray(): array
    {
        return ["redirectUri" => $this->redirectUri];
    }
}
