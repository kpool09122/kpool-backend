<?php

declare(strict_types=1);

namespace Tests\Helper;

use Application\Models\Wiki\WikiHistory;
use Source\Analyze\Shared\Infrastructure\Client\MockGoogleAnalyticsClient;

class AnalyzeSourceRecords
{
    public static function history(string $action, string $at, ?string $country = 'JP', ?string $wiki = MockGoogleAnalyticsClient::WIKI_IDENTIFIER, ?string $draft = null): WikiHistory
    {
        return WikiHistory::query()->create([
            'id' => StrTestHelper::generateUuid(), 'actor_id' => StrTestHelper::generateUuid(), 'subject_name' => 'test',
            'action_type' => $action, 'to_status' => 'under_review', 'recorded_at' => $at, 'wiki_id' => $wiki, 'draft_wiki_id' => $draft,
            'translation_set_identifier' => MockGoogleAnalyticsClient::TRANSLATION_SET_IDENTIFIER, 'language' => 'ja', 'visitor_country' => $country,
        ]);
    }
}
