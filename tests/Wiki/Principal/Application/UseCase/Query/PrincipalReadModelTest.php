<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Query;

use Source\Wiki\Principal\Application\UseCase\Query\PrincipalReadModel;
use Tests\TestCase;

class PrincipalReadModelTest extends TestCase
{
    public function testToArray(): void
    {
        $readModel = new PrincipalReadModel(
            principalIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            identityIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f201',
            isDelegatedPrincipal: true,
            isEnabled: false,
        );

        $this->assertSame([
            'principalIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            'identityIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f201',
            'isDelegatedPrincipal' => true,
            'isEnabled' => false,
        ], $readModel->toArray());
    }
}
