<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query;

use Source\Wiki\Wiki\Application\UseCase\Query\TranslationSetMasterSearchItemReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\TranslationSetMasterSearchWikiReadModel;
use Tests\TestCase;

class TranslationSetMasterSearchItemReadModelTest extends TestCase
{
    public function testToArray(): void
    {
        $wiki = new TranslationSetMasterSearchWikiReadModel(
            wikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            language: 'ja',
            name: 'IVE',
            slug: 'gr-ive-ja',
        );
        $readModel = new TranslationSetMasterSearchItemReadModel(
            translationSetIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f001',
            resourceType: 'group',
            wikis: [$wiki],
        );

        $this->assertSame([
            'translationSetIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f001',
            'resourceType' => 'group',
            'wikis' => [$wiki->toArray()],
        ], $readModel->toArray());
    }
}
