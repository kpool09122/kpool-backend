<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query;

readonly class ContactDetailReadModel
{
    /** @param array<int, array{replyIdentifier: string, content: string, sentAt: string}> $replies */
    public function __construct(
        private string $contactIdentifier,
        private ?string $identityIdentifier,
        private int $category,
        private string $name,
        private string $createdAt,
        private string $content,
        private array $replies,
    ) {
    }

    /** @return array{contactIdentifier: string, identityIdentifier: ?string, category: int, name: string, createdAt: string, content: string, replies: array<int, array{replyIdentifier: string, content: string, sentAt: string}>} */
    public function toArray(): array
    {
        return [
            'contactIdentifier' => $this->contactIdentifier,
            'identityIdentifier' => $this->identityIdentifier,
            'category' => $this->category,
            'name' => $this->name,
            'createdAt' => $this->createdAt,
            'content' => $this->content,
            'replies' => $this->replies,
        ];
    }
}
