<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Wiki\Application\UseCase\Query\ListSitemapWikis\ListSitemapWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListSitemapWikis\ListSitemapWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListSitemapWikis\ListSitemapWikisOutput;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\CreateDraftWiki;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListSitemapWikisTest extends TestCase
{
    #[Group('useDb')]
    public function testAllLanguagesAndResourceTypesWithoutDraftsOrHeavyData(): void
    {
        $expected = [];
        foreach (['agency', 'group', 'talent', 'song'] as $type) {
            foreach (Language::cases() as $language) {
                $id = StrTestHelper::generateUuid();
                $slug = $type . '-' . $language->value;
                CreateWiki::create($id, $type, [
                    'slug' => $slug,
                    'language' => $language->value,
                    'sections' => '[{"secret":"not returned"}]',
                    'published_at' => '2026-10-01 12:34:56',
                ]);
                CreateDraftWiki::create(StrTestHelper::generateUuid(), $type, ['language' => $language->value]);
                $expected[$id] = [
                    'slug' => $slug,
                    'language' => $language->value,
                    'resourceType' => $type,
                    'lastModifiedAt' => '2026-10-01T12:34:56+00:00',
                ];
            }
        }
        ksort($expected);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $payload = $this->process();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertSame(['wikis' => array_values($expected), 'nextCursor' => null], $payload);
        $this->assertCount(1, $queries);
        $this->assertStringContainsString('select "id", "slug", "language", "resource_type", "published_at" from "wikis"', $queries[0]['query']);
        $this->assertStringNotContainsString('join', $queries[0]['query']);
    }

    #[Group('useDb')]
    public function testCursorPagesAreStableAndSurviveDeletionOfPreviousPage(): void
    {
        $ids = [
            '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f102',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f103',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f104',
        ];
        foreach (array_reverse($ids) as $id) {
            CreateWiki::create($id, 'group', ['slug' => 'gr-' . $id]);
        }
        $first = $this->process(new ListSitemapWikisInput(perPage: 2));
        $this->assertSame(['gr-' . $ids[0], 'gr-' . $ids[1]], array_column($first['wikis'], 'slug'));
        $this->assertSame($ids[1], $first['nextCursor']);
        DB::table('wikis')->whereIn('id', [$ids[0], $ids[1]])->delete();

        $second = $this->process(new ListSitemapWikisInput(perPage: 2, after: new WikiIdentifier($ids[1])));
        $this->assertSame(['gr-' . $ids[2], 'gr-' . $ids[3]], array_column($second['wikis'], 'slug'));
        $this->assertNull($second['nextCursor']);
        $this->assertSame(['wikis' => [], 'nextCursor' => null], $this->process(new ListSitemapWikisInput(after: new WikiIdentifier($ids[3]))));
    }

    #[Group('useDb')]
    public function testPublicationTranslationRepublicationAndDeletionAreReflectedOnNextEnumeration(): void
    {
        $id = StrTestHelper::generateUuid();
        CreateWiki::create($id, 'talent', ['slug' => 'tl-artist', 'language' => 'ja', 'published_at' => '2026-10-01 00:00:00']);
        $this->assertSame('2026-10-01T00:00:00+00:00', $this->process()['wikis'][0]['lastModifiedAt']);

        DB::table('wikis')->where('id', $id)->update(['updated_at' => '2026-10-02 00:00:00', 'owner_account_id' => StrTestHelper::generateUuid()]);
        $this->assertSame('2026-10-01T00:00:00+00:00', $this->process()['wikis'][0]['lastModifiedAt']);

        DB::table('wikis')->where('id', $id)->update(['published_at' => '2026-10-03 00:00:00', 'sections' => '[{"content":"republished"}]']);
        $this->assertSame('2026-10-03T00:00:00+00:00', $this->process()['wikis'][0]['lastModifiedAt']);

        CreateWiki::create(StrTestHelper::generateUuid(), 'talent', ['slug' => 'tl-artist', 'language' => 'en', 'published_at' => '2026-10-04 00:00:00']);
        $this->assertCount(2, $this->process()['wikis']);
        DB::table('wikis')->where('id', $id)->delete();
        $payload = $this->process();
        $this->assertCount(1, $payload['wikis']);
        $this->assertSame('en', $payload['wikis'][0]['language']);
    }

    #[Group('useDb')]
    public function testUnknownPublicationTimeRemainsNullInsteadOfUsingDatabaseUpdateTime(): void
    {
        $id = StrTestHelper::generateUuid();
        CreateWiki::create($id, 'song', ['published_at' => null]);
        DB::table('wikis')->where('id', $id)->update(['updated_at' => '2026-10-02 00:00:00']);

        $this->assertNull($this->process()['wikis'][0]['lastModifiedAt']);
    }

    #[Group('useDb')]
    public function testDefaultLimitAndMaximumLimit(): void
    {
        $rows = [];
        for ($i = 0; $i < 1001; ++$i) {
            $rows[] = [
                'id' => StrTestHelper::generateUuid(),
                'translation_set_identifier' => StrTestHelper::generateUuid(),
                'slug' => 'gr-' . $i,
                'language' => 'ja',
                'resource_type' => 'group',
            ];
        }
        DB::table('wikis')->insert($rows);

        $default = $this->process();
        $this->assertCount(500, $default['wikis']);
        $this->assertNotNull($default['nextCursor']);
        $maximum = $this->process(new ListSitemapWikisInput(perPage: 1000));
        $this->assertCount(1000, $maximum['wikis']);
        $this->assertNotNull($maximum['nextCursor']);
        $last = $this->process(new ListSitemapWikisInput(perPage: 1000, after: new WikiIdentifier($maximum['nextCursor'])));
        $this->assertCount(1, $last['wikis']);
        $this->assertNull($last['nextCursor']);
        $this->assertCount(1001, array_unique([...array_column($maximum['wikis'], 'slug'), ...array_column($last['wikis'], 'slug')]));
    }

    /**
     * @phpstan-impure
     * @return array{wikis: list<array{slug: string, language: string, resourceType: string, lastModifiedAt: string|null}>, nextCursor: string|null}
     */
    private function process(?ListSitemapWikisInput $input = null): array
    {
        $output = new ListSitemapWikisOutput();
        $this->app()->make(ListSitemapWikisInterface::class)->process($input ?? new ListSitemapWikisInput(), $output);

        return $output->toArray();
    }

    #[Group('useDb')]
    public function testEmptyListTerminatesEnumeration(): void
    {
        $output = new ListSitemapWikisOutput();
        $this->app()->make(ListSitemapWikisInterface::class)->process(new ListSitemapWikisInput(), $output);

        $this->assertSame(['wikis' => [], 'nextCursor' => null], $output->toArray());
    }
}
