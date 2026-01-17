<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\ApproveAgency;

use Source\Wiki\Agency\Domain\Entity\DraftAgency;

class ApproveAgencyOutput implements ApproveAgencyOutputPort
{
    private ?DraftAgency $draftAgency = null;

    public function setDraftAgency(DraftAgency $draftAgency): void
    {
        $this->draftAgency = $draftAgency;
    }

    /**
     * @return array{language: ?string, name: ?string, CEO: ?string, foundedIn: ?string, description: ?string, status: ?string}
     */
    public function toArray(): array
    {
        if ($this->draftAgency === null) {
            return [
                'language' => null,
                'name' => null,
                'CEO' => null,
                'foundedIn' => null,
                'description' => null,
                'status' => null,
            ];
        }

        return [
            'language' => $this->draftAgency->language()->value,
            'name' => (string) $this->draftAgency->name(),
            'CEO' => (string) $this->draftAgency->CEO(),
            'foundedIn' => $this->draftAgency->foundedIn() !== null ? (string) $this->draftAgency->foundedIn() : null,
            'description' => (string) $this->draftAgency->description(),
            'status' => $this->draftAgency->status()->value,
        ];
    }
}
