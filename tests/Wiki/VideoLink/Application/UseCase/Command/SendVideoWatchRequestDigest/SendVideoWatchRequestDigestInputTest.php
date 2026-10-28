<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Email;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest\SendVideoWatchRequestDigestInput;

class SendVideoWatchRequestDigestInputTest extends TestCase
{
    public function testPreservesExecutionInstantAndTimezone(): void
    {
        $executedAt = new DateTimeImmutable('2026-10-29T08:59:59+09:00');
        $input = new SendVideoWatchRequestDigestInput($executedAt);
        self::assertSame($executedAt, $input->executedAt());
        self::assertSame('2026-10-29T08:59:59+09:00', $input->executedAt()->format('c'));
        self::assertNull($input->recipientEmail());
    }

    public function testPreservesOptionalRecipientFilter(): void
    {
        $email = new Email('owner@example.com');
        $input = new SendVideoWatchRequestDigestInput(new DateTimeImmutable('2026-10-29T09:00:00+09:00'), $email);
        self::assertSame($email, $input->recipientEmail());
    }
}
