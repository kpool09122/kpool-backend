<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Contact\Application\UseCase\Query\ContactDetailReadModel;

class ContactDetailReadModelTest extends TestCase
{
    /** @return array<string, array{?string, ?string}> */
    public static function authors(): array
    {
        return [
            'current author' => ['00000000-0000-7000-8000-000000000004', '佐藤 花子'],
            'missing author' => ['00000000-0000-7000-8000-000000000004', null],
            'no stored ID' => [null, null],
        ];
    }

    #[DataProvider('authors')]
    public function testPreservesOperatorReplyFieldsIncludingNulls(?string $principalIdentifier, ?string $name): void
    {
        $replies = [[
            'replyIdentifier' => '00000000-0000-7000-8000-000000000003',
            'content' => '返信内容',
            'sentAt' => '2026-08-28T12:00:00+00:00',
            'replyNumber' => 1,
            'createdAt' => '2026-08-28T11:00:00+00:00',
            'principalIdentifier' => $principalIdentifier,
            'name' => $name,
        ]];
        $detail = new ContactDetailReadModel('00000000-0000-7000-8000-000000000001', null, 1, '問い合わせ者', '2026-08-27T12:00:00+00:00', 'お問い合わせ内容', $replies);

        $this->assertSame($replies, $detail->toArray()['replies']);
    }

    public function testToArray(): void
    {
        $readModel = new ContactDetailReadModel(
            contactIdentifier: '00000000-0000-0000-0000-000000000001',
            principalIdentifier: null,
            category: 1,
            name: '問い合わせ太郎',
            createdAt: '2026-08-27T12:00:00+00:00',
            content: 'お問い合わせ内容',
            replies: [['replyIdentifier' => '00000000-0000-0000-0000-000000000003', 'content' => '返信内容', 'sentAt' => '2026-08-28T12:00:00+00:00', 'replyNumber' => 1, 'principalIdentifier' => null, 'name' => null, 'createdAt' => '2026-08-28T11:00:00+00:00']],
        );

        $this->assertSame([
            'contactIdentifier' => '00000000-0000-0000-0000-000000000001',
            'principalIdentifier' => null,
            'category' => 1,
            'name' => '問い合わせ太郎',
            'createdAt' => '2026-08-27T12:00:00+00:00',
            'content' => 'お問い合わせ内容',
            'replies' => [['replyIdentifier' => '00000000-0000-0000-0000-000000000003', 'content' => '返信内容', 'sentAt' => '2026-08-28T12:00:00+00:00', 'replyNumber' => 1, 'principalIdentifier' => null, 'name' => null, 'createdAt' => '2026-08-28T11:00:00+00:00']],
        ], $readModel->toArray());
    }
}
