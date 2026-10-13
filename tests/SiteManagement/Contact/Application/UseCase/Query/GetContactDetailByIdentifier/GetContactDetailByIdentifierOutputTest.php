<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query\GetContactDetailByIdentifier;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Contact\Application\UseCase\Query\ContactDetailReadModel;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetailByIdentifier\GetContactDetailByIdentifierOutput;

class GetContactDetailByIdentifierOutputTest extends TestCase
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
        $output = new GetContactDetailByIdentifierOutput();
        $output->output($detail);

        $this->assertSame($detail->toArray(), $output->toArray());
        $this->assertSame($owner, $output->toArray()['principalIdentifier']);
        $this->assertArrayNotHasKey('email', $output->toArray());
    }

    public function testEmptyOutput(): void
    {
        $this->assertSame([], (new GetContactDetailByIdentifierOutput())->toArray());
    }
}
