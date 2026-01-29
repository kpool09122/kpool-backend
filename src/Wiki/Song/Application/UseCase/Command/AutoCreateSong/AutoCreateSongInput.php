<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\AutoCreateSong;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Domain\ValueObject\AutoSongCreationPayload;

readonly class AutoCreateSongInput implements AutoCreateSongInputPort
{
    public function __construct(
        private AutoSongCreationPayload $payload,
        private PrincipalIdentifier     $principalIdentifier,
    ) {
    }

    public function payload(): AutoSongCreationPayload
    {
        return $this->payload;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
