<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Repository;

use Application\Models\Wiki\WikiHistory as WikiHistoryModel;
use Source\Wiki\Wiki\Domain\Entity\WikiHistory;
use Source\Wiki\Wiki\Domain\Repository\WikiHistoryRepositoryInterface;

readonly class WikiHistoryRepository implements WikiHistoryRepositoryInterface
{
    public function save(WikiHistory $wikiHistory): void
    {
        WikiHistoryModel::query()->create([
            'id' => (string) $wikiHistory->historyIdentifier(),
            'action_type' => $wikiHistory->actionType()->value,
            'actor_id' => (string) $wikiHistory->actorIdentifier(),
            'submitter_id' => $wikiHistory->submitterIdentifier() ? (string) $wikiHistory->submitterIdentifier() : null,
            'wiki_id' => $wikiHistory->wikiIdentifier() ? (string) $wikiHistory->wikiIdentifier() : null,
            'draft_wiki_id' => $wikiHistory->draftWikiIdentifier() ? (string) $wikiHistory->draftWikiIdentifier() : null,
            'from_status' => $wikiHistory->fromStatus()?->value,
            'to_status' => $wikiHistory->toStatus()?->value,
            'from_version' => $wikiHistory->fromVersion()?->value(),
            'to_version' => $wikiHistory->toVersion()?->value(),
            'subject_name' => (string) $wikiHistory->subjectName(),
            'recorded_at' => $wikiHistory->recordedAt(),
        ]);
    }
}
