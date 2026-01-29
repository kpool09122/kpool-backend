<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Application\DTO;

final readonly class SourceReference
{
    public function __construct(
        private string $uri,
        private string $title,
    ) {
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function title(): string
    {
        return $this->title;
    }
}
