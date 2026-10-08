<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query;

readonly class ContactReadModel
{
    public function __construct(
        private string $contactIdentifier,
        private ?string $principalIdentifier,
        private int $category,
        private string $name,
        /** @var array<int, string> */
        private array $replyIdentifiers,
        private string $createdAt,
    ) {
    }

    /**
     * @return array{
     *     contactIdentifier: string,
     *     principalIdentifier: ?string,
     *     category: int,
     *     name: string,
     *     replyIdentifiers: array<int, string>,
     *     createdAt: string
     * }
     */
    public function toArray(): array
    {
        return [
            'contactIdentifier' => $this->contactIdentifier,
            'principalIdentifier' => $this->principalIdentifier,
            'category' => $this->category,
            'name' => $this->name,
            'replyIdentifiers' => $this->replyIdentifiers,
            'createdAt' => $this->createdAt,
        ];
    }
}
