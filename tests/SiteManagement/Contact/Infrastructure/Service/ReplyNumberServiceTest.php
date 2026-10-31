<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Service;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\SiteManagement\Contact\Domain\Service\ReplyNumberServiceInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ReplyNumberServiceTest extends TestCase
{
    /** @return array<string, array{?string, ?string}> */
    public static function deliveryStates(): array
    {
        return [
            'pending' => [null, null],
            'sent' => ['2026-10-31 10:00:00', null],
            'failed' => [null, '2026-10-31 10:00:00'],
        ];
    }

    #[Group('useDb')]
    #[DataProvider('deliveryStates')]
    public function testNextNumberIsScopedToContactAndIncludesEveryDeliveryState(?string $sentAt, ?string $failedAt): void
    {
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $anotherContactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        foreach ([$contactIdentifier, $anotherContactIdentifier] as $identifier) {
            DB::table('site_management_contacts')->insert([
                'id' => (string) $identifier,
                'category' => Category::SUGGESTIONS->value,
                'name' => '問い合わせ者',
                'email' => 'encrypted',
                'content' => 'お問い合わせ内容',
            ]);
        }
        $replyNumberService = $this->app()->make(ReplyNumberServiceInterface::class);
        $this->assertSame(1, $replyNumberService->next($contactIdentifier));
        foreach ([[$contactIdentifier, 9], [$contactIdentifier, 3], [$anotherContactIdentifier, 100]] as [$identifier, $number]) {
            DB::table('site_management_contact_replies')->insert([
                'id' => StrTestHelper::generateUuid(),
                'contact_id' => (string) $identifier,
                'reply_number' => $number,
                'content' => '返信内容',
                'to_email' => 'encrypted',
                'sent_at' => $sentAt,
                'failed_at' => $failedAt,
            ]);
        }

        $this->assertSame(10, $replyNumberService->next($contactIdentifier));
        $this->assertSame(101, $replyNumberService->next($anotherContactIdentifier));
    }
}
