<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query;

use Source\Wiki\Wiki\Application\UseCase\Query\WikiMasterSearchItemReadModel;
use Tests\TestCase;

class WikiMasterSearchItemReadModelTest extends TestCase
{
    public function testToArray(): void
    {
        $readModel = new WikiMasterSearchItemReadModel(
            id: '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            name: 'Minji',
            slug: 'tl-minji',
            resourceType: 'talent',
        );

        $this->assertSame([
            'id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            'name' => 'Minji',
            'slug' => 'tl-minji',
            'resourceType' => 'talent',
        ], $readModel->toArray());
    }
}
