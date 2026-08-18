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
            email: 'contact@example.com',
            content: 'お問い合わせ内容',
        );
        $output = new ListMyContactsOutput();

        $output->output([$contact]);

        $this->assertSame([
            [
                'contactIdentifier' => '00000000-0000-0000-0000-000000000001',
                'identityIdentifier' => '00000000-0000-0000-0000-000000000002',
                'category' => 1,
                'name' => '問い合わせ太郎',
                'email' => 'contact@example.com',
                'content' => 'お問い合わせ内容',
            ],
        ], $output->toArray());
    }

    public function testToArrayReturnsEmptyArrayByDefault(): void
    {
        $this->assertSame([], (new ListMyContactsOutput())->toArray());
    }
}
