<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query;

readonly class ContactReadModel
{
    public function __construct(
        private string $contactIdentifier,
        private ?string $identityIdentifier,
        private int $category,
        private string $name,
        private string $email,
        private string $content,
    ) {
    }

    /**
     * @return array{
     *     contactIdentifier: string,
     *     identityIdentifier: ?string,
     *     category: int,
     *     name: string,
     *     email: string,
     *     content: string
     * }
     */
    public function toArray(): array
    {
        return [
            'contactIdentifier' => $this->contactIdentifier,
            'identityIdentifier' => $this->identityIdentifier,
            'category' => $this->category,
            'name' => $this->name,
            'email' => $this->email,
            'content' => $this->content,
        ];
    }
}
