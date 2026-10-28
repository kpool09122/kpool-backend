<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequestDigest;

use DateTimeZone;
use Psr\Log\LoggerInterface;
use Source\Shared\Application\Service\AccountNotificationRecipientServiceInterface;
use Source\Wiki\VideoLink\Application\Service\VideoWatchRequestDigestMailServiceInterface;
use Source\Wiki\VideoLink\Application\Service\VideoWatchRequestDigestQueryServiceInterface;
use Throwable;

readonly class SendVideoWatchRequestDigest implements SendVideoWatchRequestDigestInterface
{
    public function __construct(
        private VideoWatchRequestDigestQueryServiceInterface $videoWatchRequestDigestQueryService,
        private AccountNotificationRecipientServiceInterface $accountNotificationRecipientService,
        private VideoWatchRequestDigestMailServiceInterface $videoWatchRequestDigestMailService,
        private LoggerInterface $logger,
    ) {
    }

    public function process(SendVideoWatchRequestDigestInputPort $input, SendVideoWatchRequestDigestOutputPort $output): void
    {
        $now = $input->executedAt()->setTimezone(new DateTimeZone('Asia/Seoul'));
        $periodEnd = $now->setTime(9, 0);
        if ($now < $periodEnd) {
            $periodEnd = $periodEnd->modify('-1 day');
        }
        $periodEnd = $periodEnd->setTimezone(new DateTimeZone('UTC'));
        $recipientEmail = $input->recipientEmail();
        $failedCount = 0;
        foreach ($this->videoWatchRequestDigestQueryService->findByPeriod($periodEnd->modify('-1 day'), $periodEnd->modify('-7 days'), $periodEnd) as $digest) {
            foreach ($this->accountNotificationRecipientService->resolveActive([$digest->ownerAccountIdentifier]) as $recipient) {
                if ($recipientEmail !== null && strcasecmp((string) $recipient->email(), (string) $recipientEmail) !== 0) {
                    continue;
                }

                try {
                    $this->videoWatchRequestDigestMailService->send($recipient, $digest);
                } catch (Throwable $exception) {
                    $failedCount++;
                    // Transport exceptions may contain addresses, credentials or provider responses.
                    $this->logger->error('Video watch request digest delivery failed', ['exception_type' => $exception::class]);
                }
            }
        }
        $output->setFailedCount($failedCount);
    }
}
