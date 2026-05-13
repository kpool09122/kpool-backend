<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\ListWikis\ListWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListWikis\ListWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListWikis\ListWikisOutput;
use Tests\Helper\CreateWiki;
use Tests\TestCase;

class ListWikisTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsDefaultPaginationSortedByUpdatedAtDesc(): void
    {
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f101', 'talent', 'tl-alpha', 'Alpha', 'alpha', '2026-05-01 00:00:00');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f102', 'group', 'gr-beta', 'Beta', 'beta', '2026-05-03 00:00:00');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f103', 'agency', 'ag-gamma', 'Gamma', 'gamma', '2026-05-02 00:00:00');

        $payload = $this->process(new ListWikisInput(Language::KOREAN))->toArray();

        $this->assertSame(1, $payload['current_page']);
        $this->assertSame(1, $payload['last_page']);
        $this->assertSame(3, $payload['total']);
        $this->assertSame(10, $payload['per_page']);
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f102',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f103',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
        ], array_column($payload['wikis'], 'wikiIdentifier'));
        $this->assertArrayHasKey('translationSetIdentifier', $payload['wikis'][0]);
        $this->assertIsString($payload['wikis'][0]['translationSetIdentifier']);
        $this->assertArrayNotHasKey('sections', $payload['wikis'][0]);
    }

    #[Group('useDb')]
    public function testProcessAppliesPerPage(): void
    {
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f201', 'talent', 'tl-alpha', 'Alpha', 'alpha', '2026-05-01 00:00:00');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f202', 'group', 'gr-beta', 'Beta', 'beta', '2026-05-02 00:00:00');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f203', 'agency', 'ag-gamma', 'Gamma', 'gamma', '2026-05-03 00:00:00');

        $payload = $this->process(new ListWikisInput(language: Language::KOREAN, perPage: 2))->toArray();

        $this->assertCount(2, $payload['wikis']);
        $this->assertSame(2, $payload['per_page']);
        $this->assertSame(2, $payload['last_page']);
        $this->assertSame(3, $payload['total']);
    }

    #[Group('useDb')]
    public function testProcessFiltersByResourceType(): void
    {
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f301', 'talent', 'tl-alpha', 'Alpha', 'alpha', '2026-05-01 00:00:00');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f302', 'group', 'gr-beta', 'Beta', 'beta', '2026-05-02 00:00:00');

        $payload = $this->process(new ListWikisInput(language: Language::KOREAN, resourceType: ResourceType::GROUP))->toArray();

        $this->assertSame(1, $payload['total']);
        $this->assertSame('group', $payload['wikis'][0]['resourceType']);
        $this->assertSame('Beta', $payload['wikis'][0]['name']);
    }

    #[Group('useDb')]
    public function testProcessSearchesKeywordByNormalizedNamePrefix(): void
    {
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f401', 'talent', 'tl-chaeyoung', 'Chaeyoung', 'chaeyoung', '2026-05-01 00:00:00');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f402', 'agency', 'ag-chae-agency', 'Chae Agency', 'chaeagency', '2026-05-02 00:00:00');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f403', 'group', 'gr-twice', 'TWICE', 'twice', '2026-05-03 00:00:00');

        $payload = $this->process(new ListWikisInput(language: Language::KOREAN, keyword: 'chae', sort: 'name', order: 'asc'))->toArray();

        $this->assertSame(2, $payload['total']);
        $this->assertSame(['Chae Agency', 'Chaeyoung'], array_column($payload['wikis'], 'name'));
    }

    #[Group('useDb')]
    public function testProcessSortsByNameAndOrder(): void
    {
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f501', 'talent', 'tl-charlie', 'Charlie', 'charlie', '2026-05-01 00:00:00');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f502', 'song', 'sg-alpha', 'Alpha', 'alpha', '2026-05-02 00:00:00');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f503', 'group', 'gr-bravo', 'Bravo', 'bravo', '2026-05-03 00:00:00');

        $payload = $this->process(new ListWikisInput(language: Language::KOREAN, sort: 'name', order: 'desc'))->toArray();

        $this->assertSame(['Charlie', 'Bravo', 'Alpha'], array_column($payload['wikis'], 'name'));
    }

    #[Group('useDb')]
    public function testProcessSortsByUpdatedAtAscending(): void
    {
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f601', 'talent', 'tl-alpha', 'Alpha', 'alpha', '2026-05-01 00:00:00');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f602', 'group', 'gr-beta', 'Beta', 'beta', '2026-05-02 00:00:00');

        $payload = $this->process(new ListWikisInput(language: Language::KOREAN, sort: 'updatedAt', order: 'asc'))->toArray();

        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f601',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f602',
        ], array_column($payload['wikis'], 'wikiIdentifier'));
    }

    #[Group('useDb')]
    public function testProcessFiltersByLanguage(): void
    {
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f701', 'talent', 'tl-alpha-ko', 'Alpha KO', 'alpha', '2026-05-01 00:00:00', 'ko');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f702', 'talent', 'tl-alpha-ja', 'Alpha JA', 'alpha', '2026-05-02 00:00:00', 'ja');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217f703', 'talent', 'tl-alpha-en', 'Alpha EN', 'alpha', '2026-05-03 00:00:00', 'en');

        $koPayload = $this->process(new ListWikisInput(Language::KOREAN))->toArray();
        $jaPayload = $this->process(new ListWikisInput(Language::JAPANESE))->toArray();
        $enPayload = $this->process(new ListWikisInput(Language::ENGLISH))->toArray();

        $this->assertSame(1, $koPayload['total']);
        $this->assertSame('ko', $koPayload['wikis'][0]['language']);
        $this->assertSame('Alpha KO', $koPayload['wikis'][0]['name']);
        $this->assertSame(1, $jaPayload['total']);
        $this->assertSame('ja', $jaPayload['wikis'][0]['language']);
        $this->assertSame('Alpha JA', $jaPayload['wikis'][0]['name']);
        $this->assertSame(1, $enPayload['total']);
        $this->assertSame('en', $enPayload['wikis'][0]['language']);
        $this->assertSame('Alpha EN', $enPayload['wikis'][0]['name']);
    }

    private function listWikis(): ListWikisInterface
    {
        return $this->app->make(ListWikisInterface::class);
    }

    private function process(ListWikisInput $input): ListWikisOutput
    {
        $output = new ListWikisOutput();
        $this->listWikis()->process($input, $output);

        return $output;
    }

    private function createWiki(
        string $wikiId,
        string $resourceType,
        string $slug,
        string $name,
        string $normalizedName,
        string $updatedAt,
        string $language = 'ko',
    ): void {
        CreateWiki::create(
            $wikiId,
            $resourceType,
            [
                'slug' => $slug,
                'language' => $language,
                'published_at' => '2026-04-01 00:00:00',
            ],
            [
                'name' => $name,
                'normalized_name' => $normalizedName,
            ],
        );

        DB::table('wikis')
            ->where('id', $wikiId)
            ->update([
                'updated_at' => $updatedAt,
                'created_at' => $updatedAt,
            ]);
    }
}
