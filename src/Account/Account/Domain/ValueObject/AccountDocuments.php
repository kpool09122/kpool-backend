<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

class AccountDocuments
{
    /** @var array<string, AccountDocument> */
    private array $documents;

    /**
     * @param AccountDocument[] $documents
     */
    public function __construct(array $documents = [])
    {
        $this->documents = [];
        foreach ($documents as $document) {
            $this->documents[$document->documentType()->value] = $document;
        }
    }

    public function add(AccountDocument $document): void
    {
        $this->documents[$document->documentType()->value] = $document;
    }

    /** @param AccountDocument[] $documents */
    public function replaceWith(array $documents): void
    {
        $this->documents = [];
        foreach ($documents as $document) {
            $this->add($document);
        }
    }

    /** @return AccountDocument[] */
    public function all(): array
    {
        return array_values($this->documents);
    }

    /** @return DocumentType[] */
    public function documentTypes(): array
    {
        return array_map(static fn (AccountDocument $document) => $document->documentType(), $this->all());
    }
}
