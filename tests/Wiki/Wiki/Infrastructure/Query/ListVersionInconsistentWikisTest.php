<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Wiki\Wiki\Application\UseCase\Query\ListVersionInconsistentWikis\ListVersionInconsistentWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListVersionInconsistentWikis\ListVersionInconsistentWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListVersionInconsistentWikis\ListVersionInconsistentWikisOutput;
use Tests\Helper\CreateWiki;
use Tests\TestCase;

class ListVersionInconsistentWikisTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessDoesNotReturnTranslationSetWhenAllLanguagesHaveSameVersion(): void
    {
        $translationSetIdentifier = '01965bb2-bcc9-7c6f-8b90-89f7f217a101';
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217a102', $translationSetIdentifier, 'ko', 2);
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217a103', $translationSetIdentifier, 'ja', 2);
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217a104', $translationSetIdentifier, 'en', 2);

        $payload = $this->process(new ListVersionInconsistentWikisInput())->toArray();

        $this->assertSame(0, $payload['total']);
        $this->assertSame([], $payload['wikis']);
    }

    #[Group('useDb')]
    public function testProcessReturnsLatestVersionWikiWhenVersionsDiffer(): void
    {
        $translationSetIdentifier = '01965bb2-bcc9-7c6f-8b90-89f7f217b101';
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217b102', $translationSetIdentifier, 'ko', 3);
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217b103', $translationSetIdentifier, 'ja', 2);
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217b104', $translationSetIdentifier, 'en', 2);

        $payload = $this->process(new ListVersionInconsistentWikisInput())->toArray();

        $this->assertSame(1, $payload['total']);
        $this->assertSame($translationSetIdentifier, $payload['wikis'][0]['translationSetIdentifier']);
        $this->assertSame('ko', $payload['wikis'][0]['language']);
        $this->assertSame(3, $payload['wikis'][0]['version']);
        $this->assertSame('Talent ko v3 Wiki', $payload['wikis'][0]['title']);
        $this->assertSame('Talent ko v3 profile.', $payload['wikis'][0]['metaDescription']);
        $this->assertSame(['Talent ko v3', 'talent'], $payload['wikis'][0]['keywords']);
    }

    #[Group('useDb')]
    public function testProcessReturnsTranslationSetWhenPublishedWikiIsMissingForSomeLanguages(): void
    {
        $translationSetIdentifier = '01965bb2-bcc9-7c6f-8b90-89f7f217c101';
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217c102', $translationSetIdentifier, 'ko', 1);

        $payload = $this->process(new ListVersionInconsistentWikisInput())->toArray();

        $this->assertSame(1, $payload['total']);
        $this->assertSame($translationSetIdentifier, $payload['wikis'][0]['translationSetIdentifier']);
        $this->assertSame('ko', $payload['wikis'][0]['language']);
        $this->assertSame(1, $payload['wikis'][0]['version']);
    }

    #[Group('useDb')]
    public function testProcessReturnsAllWikisWhenMultipleLanguagesHaveLatestVersion(): void
    {
        $translationSetIdentifier = '01965bb2-bcc9-7c6f-8b90-89f7f217d101';
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217d102', $translationSetIdentifier, 'ko', 3);
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217d103', $translationSetIdentifier, 'ja', 3);
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f217d104', $translationSetIdentifier, 'en', 2);

        $payload = $this->process(new ListVersionInconsistentWikisInput())->toArray();

        $this->assertSame(2, $payload['total']);
        $this->assertEqualsCanonicalizing(['ko', 'ja'], array_column($payload['wikis'], 'language'));
        $this->assertSame([3, 3], array_column($payload['wikis'], 'version'));
        $this->assertSame(
            [$translationSetIdentifier, $translationSetIdentifier],
            array_column($payload['wikis'], 'translationSetIdentifier'),
        );
    }

    private function listVersionInconsistentWikis(): ListVersionInconsistentWikisInterface
    {
        return $this->app->make(ListVersionInconsistentWikisInterface::class);
    }

    private function process(ListVersionInconsistentWikisInput $input): ListVersionInconsistentWikisOutput
    {
        $output = new ListVersionInconsistentWikisOutput();
        $this->listVersionInconsistentWikis()->process($input, $output);

        return $output;
    }

    private function createWiki(
        string $wikiId,
        string $translationSetIdentifier,
        string $language,
        int $version,
    ): void {
        CreateWiki::create(
            $wikiId,
            'talent',
            [
                'translation_set_identifier' => $translationSetIdentifier,
                'slug' => "tl-version-inconsistent-{$language}-{$version}-" . substr($wikiId, -4),
                'language' => $language,
                'version' => $version,
                'published_at' => '2026-04-01 00:00:00',
                'title' => "Talent {$language} v{$version} Wiki",
                'meta_description' => "Talent {$language} v{$version} profile.",
                'keywords' => json_encode(["Talent {$language} v{$version}", 'talent']),
            ],
            [
                'name' => "Talent {$language} v{$version}",
                'normalized_name' => "talent {$language} v{$version}",
            ],
        );

        DB::table('wikis')
            ->where('id', $wikiId)
            ->update([
                'updated_at' => '2026-05-01 00:00:00',
                'created_at' => '2026-05-01 00:00:00',
            ]);
    }
}
