<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Infrastructure\Service;

use Application\Models\Wiki\DraftWiki;
use Application\Models\Wiki\WikiHistory;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Source\Analyze\Wiki\Application\Service\DailyWikiOperationCount;
use Source\Analyze\Wiki\Application\Service\DailyWikiOperationsQueryServiceInterface;

readonly class DailyWikiOperationsQueryService implements DailyWikiOperationsQueryServiceInterface
{
    /**
     * @return list<DailyWikiOperationCount>
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
            ->where('action_type', 'publish')->whereNotNull('wiki_id')->orderBy('recorded_at')->orderBy('id')
            ->get(['draft_wiki_id', 'wiki_id'])->unique('draft_wiki_id')->pluck('wiki_id', 'draft_wiki_id');
        /** @var array<string, DailyWikiOperationCount> $counts */
        $counts = [];
        foreach ($histories as $history) {
            $wikiIdentifier = $history->wiki_id;
            if ($wikiIdentifier === null && $history->draft_wiki_id !== null) {
                $wikiIdentifier = $publishedWikiIdentifiers->get($history->draft_wiki_id) ?? $publicationWikiIdentifiers->get($history->draft_wiki_id);
            }
            $country = $history->visitor_country ?? '';
            $key = implode("\0", [$wikiIdentifier ?? $history->draft_wiki_id ?? '', $history->translation_set_identifier ?? '', $history->language ?? '', $country]);
            if (! isset($counts[$key])) {
                $counts[$key] = new DailyWikiOperationCount($wikiIdentifier, $history->translation_set_identifier, $history->language, $country);
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
