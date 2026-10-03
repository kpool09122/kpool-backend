<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query\ListContacts;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Contact\Application\UseCase\Query\ContactReadModel;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsOutput;

class ListContactsOutputTest extends TestCase
{
    public function testToArray(): void
    {
        $output = new ListContactsOutput();
        $output->output([new ContactReadModel(
            contactIdentifier: '00000000-0000-0000-0000-000000000001',
            identityIdentifier: null,
            category: 1,
            name: '問い合わせ太郎',
            replyIdentifiers: [],
            createdAt: '2026-08-27T12:00:00+00:00',
        )], 2, 5, 41, 10);

        $this->assertSame([
            'contacts' => [[
                'contactIdentifier' => '00000000-0000-0000-0000-000000000001',
                'identityIdentifier' => null,
                'category' => 1,
                'name' => '問い合わせ太郎',
                'replyIdentifiers' => [],
                'createdAt' => '2026-08-27T12:00:00+00:00',
            ]],
            'current_page' => 2,
            'last_page' => 5,
            'total' => 41,
            'per_page' => 10,
        ], $output->toArray());
    }
}
