<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

final readonly class TableCell
{
    public function __construct(
        private string $content,
        private ?int $colspan = null,
    ) {
    }

    public function content(): string
    {
        return $this->content;
    }

    public function colspan(): ?int
    {
        return $this->colspan;
    }

    public function withContent(string $content): self
    {
        return new self($content, $this->colspan);
    }

    /**
     * @param array{content?: string, colspan?: int} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['content'] ?? '',
            $data['colspan'] ?? null,
        );
    }

    /**
     * @return array{content: string, colspan?: int}
     */
    public function toArray(): array
    {
        $data = ['content' => $this->content];
        if ($this->colspan !== null) {
            $data['colspan'] = $this->colspan;
        }

        return $data;
    }
}
