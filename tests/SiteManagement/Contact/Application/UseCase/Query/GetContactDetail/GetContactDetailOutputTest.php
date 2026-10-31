<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Contact\Application\UseCase\Query\ContactDetailReadModel;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailOutput;

class GetContactDetailOutputTest extends TestCase
{
    /** @return array<string, array{?string}> */
    public static function owners(): array
    {
        return ['principal' => ['00000000-0000-7000-8000-000000000002'], 'anonymous' => [null]];
    }

    #[DataProvider('owners')]
    public function testPreservesExistingContactDetailIncludingNullablePrincipal(?string $owner): void
    {
        $detail = new ContactDetailReadModel('00000000-0000-7000-8000-000000000001', $owner, 1, '問い合わせ者', '2026-08-16T10:00:00+00:00', 'お問い合わせ内容', []);
        $output = new GetContactDetailOutput();
        $output->output($detail);

        $this->assertSame($detail->toArray(), $output->toArray());
        $this->assertSame($owner, $output->toArray()['principalIdentifier']);
        $this->assertArrayNotHasKey('email', $output->toArray());
    }

    public function testToArray(): void
    {
        $output = new GetContactDetailOutput();
        $output->output(new ContactDetailReadModel(
            contactIdentifier: '00000000-0000-0000-0000-000000000001',
            principalIdentifier: '00000000-0000-0000-0000-000000000002',
            category: 1,
            name: '問い合わせ太郎',
            createdAt: '2026-08-27T12:00:00+00:00',
            content: 'お問い合わせ内容',
            replies: [['replyIdentifier' => '00000000-0000-0000-0000-000000000003', 'content' => '返信内容', 'sentAt' => '2026-08-28T12:00:00+00:00', 'replyNumber' => 1, 'createdAt' => '2026-08-28T11:00:00+00:00', 'principalIdentifier' => '00000000-0000-0000-0000-000000000004', 'name' => '佐藤 花子']],
        ));

        $this->assertSame([
            'contactIdentifier' => '00000000-0000-0000-0000-000000000001',
            'principalIdentifier' => '00000000-0000-0000-0000-000000000002',
            'category' => 1,
            'name' => '問い合わせ太郎',
            'createdAt' => '2026-08-27T12:00:00+00:00',
            'content' => 'お問い合わせ内容',
            'replies' => [['replyIdentifier' => '00000000-0000-0000-0000-000000000003', 'content' => '返信内容', 'sentAt' => '2026-08-28T12:00:00+00:00', 'replyNumber' => 1, 'createdAt' => '2026-08-28T11:00:00+00:00', 'principalIdentifier' => '00000000-0000-0000-0000-000000000004', 'name' => '佐藤 花子']],
        ], $output->toArray());
    }

    public function testToArrayReturnsEmptyArrayByDefault(): void
    {
        $this->assertSame([], (new GetContactDetailOutput())->toArray());
    }
}
