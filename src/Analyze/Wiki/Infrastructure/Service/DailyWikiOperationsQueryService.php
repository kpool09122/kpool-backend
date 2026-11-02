<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Infrastructure\Service;

use Application\Models\Wiki\DraftWiki;
use Application\Models\Wiki\WikiHistory;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Source\Analyze\Wiki\Application\Exception\WikiOperationHistoryUnavailableException;
use Source\Analyze\Wiki\Application\Service\DailyWikiOperationCount;
use Source\Analyze\Wiki\Application\Service\DailyWikiOperationsQueryServiceInterface;

readonly class DailyWikiOperationsQueryService implements DailyWikiOperationsQueryServiceInterface
{
    /**
     * @return list<DailyWikiOperationCount>
     * @throws WikiOperationHistoryUnavailableException
     */
    public function find(DateTimeImmutable $start, DateTimeImmutable $endExclusive): array
    {
        $histories = WikiHistory::query()->where('recorded_at', '>=', $start)->where('recorded_at', '<', $endExclusive)
            ->where(static function (Builder $query): void {
                $query->where('action_type', 'publish')->orWhere(static fn (Builder $submission) => $submission->where('action_type', 'draft_status_change')->where('to_status', 'under_review'));
            })->get();
        $draftIdentifiers = $histories->whereNull('wiki_id')->whereNotNull('draft_wiki_id')->pluck('draft_wiki_id')->unique();
        /** @var Collection<string, string> $publishedWikiIdentifiers */
        $publishedWikiIdentifiers = $draftIdentifiers->isEmpty() ? collect() : DraftWiki::query()->whereIn('id', $draftIdentifiers)->whereNotNull('published_wiki_id')->pluck('published_wiki_id', 'id');
        $unresolvedDraftIdentifiers = $draftIdentifiers->diff($publishedWikiIdentifiers->keys());
        /** @var Collection<string, string> $publicationWikiIdentifiers */
        $publicationWikiIdentifiers = $unresolvedDraftIdentifiers->isEmpty() ? collect() : WikiHistory::query()->whereIn('draft_wiki_id', $unresolvedDraftIdentifiers)
            ->where('action_type', 'publish')->whereNotNull('wiki_id')->orderBy('recorded_at')
            ->get(['draft_wiki_id', 'wiki_id'])->unique('draft_wiki_id')->pluck('wiki_id', 'draft_wiki_id');
        /** @var array<string, DailyWikiOperationCount> $counts */
        $counts = [];
        foreach ($histories as $history) {
            $wikiIdentifier = $history->wiki_id;
            if ($wikiIdentifier === null && $history->draft_wiki_id !== null) {
                $wikiIdentifier = $publishedWikiIdentifiers->get($history->draft_wiki_id) ?? $publicationWikiIdentifiers->get($history->draft_wiki_id);
            }
            if ($wikiIdentifier === null || $history->translation_set_identifier === null || $history->language === null) {
                throw new WikiOperationHistoryUnavailableException();
            }
            $country = $history->visitor_country ?? '';
            $key = $wikiIdentifier . "\0" . $history->language . "\0" . $country;
            if (! isset($counts[$key])) {
                $counts[$key] = new DailyWikiOperationCount($wikiIdentifier, $history->translation_set_identifier, $history->language, $country);
            } elseif ($counts[$key]->translationSetIdentifier() !== $history->translation_set_identifier) {
                throw new WikiOperationHistoryUnavailableException();
            }
            if ($history->action_type === 'publish') {
                $counts[$key]->recordPublication();
            } else {
                $counts[$key]->recordSubmission();
            }
        }

        ksort($counts);

        return array_values($counts);
    }
}
