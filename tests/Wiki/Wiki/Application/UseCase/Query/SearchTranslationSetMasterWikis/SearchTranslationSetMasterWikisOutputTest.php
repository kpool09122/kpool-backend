<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\SearchTranslationSetMasterWikis;

use Source\Wiki\Wiki\Application\UseCase\Query\SearchTranslationSetMasterWikis\SearchTranslationSetMasterWikisOutput;
use Source\Wiki\Wiki\Application\UseCase\Query\TranslationSetMasterSearchItemReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\TranslationSetMasterSearchWikiReadModel;
use Tests\TestCase;

class SearchTranslationSetMasterWikisOutputTest extends TestCase
{
    public function testToArrayReturnsTranslationSetMasters(): void
    {
        $wiki = new TranslationSetMasterSearchWikiReadModel(
            wikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            language: 'ja',
            name: 'IVE',
            slug: 'gr-ive-ja',
        );
        $item = new TranslationSetMasterSearchItemReadModel(
            translationSetIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f001',
            resourceType: 'group',
            wikis: [$wiki],
        );

        $output = new SearchTranslationSetMasterWikisOutput();
        $output->output([$item]);

        $this->assertSame([
            'translationSetMasters' => [$item->toArray()],
        ], $output->toArray());
    }

    public function testToArrayReturnsEmptyTranslationSetMastersByDefault(): void
    {
        $output = new SearchTranslationSetMasterWikisOutput();

        $this->assertSame(['translationSetMasters' => []], $output->toArray());
    }
}
