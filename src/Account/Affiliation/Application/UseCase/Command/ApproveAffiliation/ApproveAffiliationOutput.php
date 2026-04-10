<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation;

use DateTimeInterface;
use Source\Account\Affiliation\Domain\Entity\Affiliation;

class ApproveAffiliationOutput implements ApproveAffiliationOutputPort
{
    private ?Affiliation $affiliation = null;

    public function setAffiliation(Affiliation $affiliation): void
    {
        $this->affiliation = $affiliation;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->affiliation === null) {
            return [];
        }

        $affiliation = $this->affiliation;
        $terms = $affiliation->terms();

        return [
            'affiliationIdentifier' => (string) $affiliation->affiliationIdentifier(),
            'agencyAccountIdentifier' => (string) $affiliation->agencyAccountIdentifier(),
            'talentAccountIdentifier' => (string) $affiliation->talentAccountIdentifier(),
            'requestedBy' => (string) $affiliation->requestedBy(),
            'status' => $affiliation->status()->value,
            'terms' => $terms === null ? null : [
                'revenueSharePercentage' => $terms->revenueSharePercentage()?->value(),
                'contractNotes' => $terms->contractNotes(),
            ],
            'requestedAt' => $affiliation->requestedAt()->format(DateTimeInterface::ATOM),
            'activatedAt' => $affiliation->activatedAt()?->format(DateTimeInterface::ATOM),
            'terminatedAt' => $affiliation->terminatedAt()?->format(DateTimeInterface::ATOM),
        ];
    }
}
