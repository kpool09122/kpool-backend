<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Contact\Application\UseCase\Query\ContactDetailReadModel;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailOutput;

class GetContactDetailOutputTest extends TestCase
{
    public function testToArray(): void
    {
        $output = new GetContactDetailOutput();
        $output->output(new ContactDetailReadModel(
            contactIdentifier: '00000000-0000-0000-0000-000000000001',
            identityIdentifier: '00000000-0000-0000-0000-000000000002',
            category: 1,
            name: '問い合わせ太郎',
            createdAt: '2026-08-27T12:00:00+00:00',
            content: 'お問い合わせ内容',
            replies: [['replyIdentifier' => '00000000-0000-0000-0000-000000000003', 'content' => '返信内容', 'sentAt' => '2026-08-28T12:00:00+00:00']],
        ));

        $this->assertSame([
            'contactIdentifier' => '00000000-0000-0000-0000-000000000001',
            'identityIdentifier' => '00000000-0000-0000-0000-000000000002',
            'category' => 1,
            'name' => '問い合わせ太郎',
            'createdAt' => '2026-08-27T12:00:00+00:00',
            'content' => 'お問い合わせ内容',
            'replies' => [['replyIdentifier' => '00000000-0000-0000-0000-000000000003', 'content' => '返信内容', 'sentAt' => '2026-08-28T12:00:00+00:00']],
        ], $output->toArray());
    }

    public function testToArrayReturnsEmptyArrayByDefault(): void
    {
        $this->assertSame([], (new GetContactDetailOutput())->toArray());
    }
}
