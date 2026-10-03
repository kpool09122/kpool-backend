<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Contact\Application\UseCase\Query\ContactDetailReadModel;

class ContactDetailReadModelTest extends TestCase
{
    public function testToArray(): void
    {
        $readModel = new ContactDetailReadModel(
            contactIdentifier: '00000000-0000-0000-0000-000000000001',
            identityIdentifier: null,
            category: 1,
            name: '問い合わせ太郎',
            createdAt: '2026-08-27T12:00:00+00:00',
            content: 'お問い合わせ内容',
            replies: [['replyIdentifier' => '00000000-0000-0000-0000-000000000003', 'content' => '返信内容', 'sentAt' => '2026-08-28T12:00:00+00:00']],
        );

        $this->assertSame([
            'contactIdentifier' => '00000000-0000-0000-0000-000000000001',
            'identityIdentifier' => null,
            'category' => 1,
            'name' => '問い合わせ太郎',
            'createdAt' => '2026-08-27T12:00:00+00:00',
            'content' => 'お問い合わせ内容',
            'replies' => [['replyIdentifier' => '00000000-0000-0000-0000-000000000003', 'content' => '返信内容', 'sentAt' => '2026-08-28T12:00:00+00:00']],
        ], $readModel->toArray());
    }
}
