<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Contact\Application\UseCase\Query\ContactReadModel;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts\ListMyContactsOutput;

class ListMyContactsOutputTest extends TestCase
{
    public function testToArray(): void
    {
        $contact = new ContactReadModel(
            contactIdentifier: '00000000-0000-0000-0000-000000000001',
            identityIdentifier: '00000000-0000-0000-0000-000000000002',
            category: 1,
            name: '問い合わせ太郎',
            replyIdentifiers: ['00000000-0000-0000-0000-000000000003'],
            createdAt: '2026-08-27T12:00:00+00:00',
        );
        $output = new ListMyContactsOutput();

        $output->output([$contact]);

        $this->assertSame([
            [
                'contactIdentifier' => '00000000-0000-0000-0000-000000000001',
                'identityIdentifier' => '00000000-0000-0000-0000-000000000002',
                'category' => 1,
                'name' => '問い合わせ太郎',
                'replyIdentifiers' => ['00000000-0000-0000-0000-000000000003'],
                'createdAt' => '2026-08-27T12:00:00+00:00',
            ],
        ], $output->toArray());
    }

    public function testToArrayReturnsEmptyArrayByDefault(): void
    {
        $this->assertSame([], (new ListMyContactsOutput())->toArray());
    }
}
