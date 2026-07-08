<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchMasterWikis\SearchMasterWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchMasterWikis\SearchMasterWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchMasterWikis\SearchMasterWikisOutput;
use Tests\Helper\CreateWiki;
use Tests\TestCase;

class SearchMasterWikisTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessSearchesEachResourceType(): void
    {
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217a001', 'agency', 'ag-starship', 'Starship Entertainment', 'starship entertainment');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217a002', 'group', 'gr-ive', 'IVE', 'ive');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217a003', 'talent', 'tl-yujin', 'An Yujin', 'an yujin');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217a004', 'song', 'sg-love-dive', 'LOVE DIVE', 'love dive');

        $this->assertSame('Starship Entertainment', $this->firstName(ResourceType::AGENCY, 'starship'));
        $this->assertSame('IVE', $this->firstName(ResourceType::GROUP, 'ive'));
        $this->assertSame('An Yujin', $this->firstName(ResourceType::TALENT, 'yujin'));
        $this->assertSame('LOVE DIVE', $this->firstName(ResourceType::SONG, 'love'));
    }

    #[Group('useDb')]
    public function testProcessSearchesNameNormalizedNameAndSlugByPartialMatch(): void
    {
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217b001', 'talent', 'tl-kim-minji', '김민지', 'kim minji');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217b002', 'talent', 'tl-hanni', 'Hanni', 'hanni');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217b003', 'talent', 'tl-danielle-marsh', 'Danielle', 'danielle');

        $this->assertSame(['김민지'], array_column($this->process(ResourceType::TALENT, '민지')->toArray()['wikis'], 'name'));
        $this->assertSame(['김민지'], array_column($this->process(ResourceType::TALENT, 'minj')->toArray()['wikis'], 'name'));
        $this->assertSame(['Danielle'], array_column($this->process(ResourceType::TALENT, 'marsh')->toArray()['wikis'], 'name'));
    }

    #[Group('useDb')]
    public function testProcessFiltersByLanguageAndPublishedWikis(): void
    {
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217c001', 'group', 'gr-newjeans-ko', 'NewJeans KO', 'newjeans', language: 'ko');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217c002', 'group', 'gr-newjeans-ja', 'NewJeans JA', 'newjeans', language: 'ja');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217c003', 'group', 'gr-newjeans-draft-like', 'NewJeans Draft', 'newjeans', publishedAt: null);

        $payload = $this->process(ResourceType::GROUP, 'newjeans')->toArray();

        $this->assertSame(['NewJeans KO'], array_column($payload['wikis'], 'name'));
    }

    #[Group('useDb')]
    public function testProcessAppliesLimit(): void
    {
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217d001', 'agency', 'ag-alpha', 'Alpha Agency', 'alpha agency');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217d002', 'agency', 'ag-beta', 'Beta Agency', 'beta agency');

        $payload = $this->process(ResourceType::AGENCY, 'agency', 1)->toArray();

        $this->assertCount(1, $payload['wikis']);
        $this->assertSame(['id', 'name', 'slug', 'resourceType'], array_keys($payload['wikis'][0]));
    }

    private function firstName(ResourceType $resourceType, string $keyword): string
    {
        $payload = $this->process($resourceType, $keyword)->toArray();

        return $payload['wikis'][0]['name'];
    }

    private function searchMasterWikis(): SearchMasterWikisInterface
    {
        return $this->app->make(SearchMasterWikisInterface::class);
    }

    private function process(ResourceType $resourceType, string $keyword, ?int $limit = null): SearchMasterWikisOutput
    {
        $output = new SearchMasterWikisOutput();
        $this->searchMasterWikis()->process(new SearchMasterWikisInput(Language::KOREAN, $resourceType, $keyword, $limit), $output);

        return $output;
    }

    private function createWiki(
        string $wikiId,
        string $resourceType,
        string $slug,
        string $name,
        string $normalizedName,
        string $language = 'ko',
        ?string $publishedAt = '2026-04-01 00:00:00',
    ): void {
        CreateWiki::create(
            $wikiId,
            $resourceType,
            [
                'slug' => $slug,
                'language' => $language,
                'published_at' => $publishedAt,
            ],
            [
                'name' => $name,
                'normalized_name' => $normalizedName,
            ],
        );

        DB::table('wikis')
            ->where('id', $wikiId)
            ->update(['updated_at' => '2026-05-01 00:00:00']);
    }
}
