<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query;

final readonly class FreeMaterialSiteReadModel
{
    public function __construct(private string $siteName, private string $pageUrl, private string $reason, private string $usageEvidence, private string $usageEvidenceUrl)
    {
    }

    /** @return array{siteName: string, pageUrl: string, reason: string, usageEvidence: string, usageEvidenceUrl: string} */
    public function toArray(): array
    {
        return ['siteName' => $this->siteName, 'pageUrl' => $this->pageUrl, 'reason' => $this->reason, 'usageEvidence' => $this->usageEvidence, 'usageEvidenceUrl' => $this->usageEvidenceUrl];
    }
}
