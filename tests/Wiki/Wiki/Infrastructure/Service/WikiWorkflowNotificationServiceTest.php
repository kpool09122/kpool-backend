<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Service;

use Application\Mail\WikiWorkflowMail;
use Application\Mail\WikiWorkflowMailType;
use Closure;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Service\WikiWorkflowNotificationServiceInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiHistoryRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiRejectionReason;
use Source\Wiki\Wiki\Infrastructure\Service\WikiWorkflowNotificationService;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class WikiWorkflowNotificationServiceTest extends TestCase
{
    public function testContainerResolvesService(): void
    {
        $this->assertInstanceOf(WikiWorkflowNotificationService::class, $this->app()->make(WikiWorkflowNotificationServiceInterface::class));
    }

    public function testQueuesEachOutcomeOnlyToSubmissionActorInTheirLanguage(): void
    {
        foreach (Language::cases() as $language) {
            foreach (WikiWorkflowMailType::cases() as $type) {
                Mail::fake();
                $id = new DraftWikiIdentifier(StrTestHelper::generateUuid());
                [$service, $submitter] = $this->service($id, $language);
                match ($type) {
                    WikiWorkflowMailType::APPROVED => $service->notifyApproved($id, 'Example'),
                    WikiWorkflowMailType::REJECTED => $service->notifyRejected($id, 'Example', ResourceType::GROUP, new DraftWikiRejectionReason('Please add sources.')),
                    WikiWorkflowMailType::PUBLISHED => $service->notifyPublished($id, 'Example', new Slug('gr-example'), Language::KOREAN),
                };
                $url = match ($type) {
                    WikiWorkflowMailType::APPROVED => null,
                    WikiWorkflowMailType::PUBLISHED => 'https://frontend.example.com/ko/wiki/gr-example',
                    WikiWorkflowMailType::REJECTED => 'https://frontend.example.com/wiki/' . $id . '/edit?resourceType=group',
                };
                Mail::assertQueued(WikiWorkflowMail::class, static fn (WikiWorkflowMail $mail): bool => $mail->hasTo((string) $submitter->email())
                    && $mail->language === $language && $mail->type === $type && $mail->wikiName === 'Example'
                    && $mail->wikiUrl === $url
                    && $mail->rejectionReason === ($type === WikiWorkflowMailType::REJECTED ? 'Please add sources.' : null));
                Mail::assertQueuedCount(1);
                Mail::assertNothingSent();
            }
        }
    }

    public function testDoesNotQueueForMissingSubmissionPrincipalOrIdentity(): void
    {
        foreach (['submission', 'principal', 'identity'] as $missing) {
            Mail::fake();
            $id = new DraftWikiIdentifier(StrTestHelper::generateUuid());
            [$service] = $this->service($id, Language::JAPANESE, $missing);
            $service->notifyApproved($id, 'Example');
            Mail::assertNothingOutgoing();
        }
    }

    public function testDefersRecipientLookupAndQueueingUntilCommit(): void
    {
        Mail::fake();
        $id = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        [$service] = $this->service($id, Language::JAPANESE);
        $afterCommit = null;
        DB::shouldReceive('transactionLevel')->once()->andReturn(1);
        DB::shouldReceive('afterCommit')->once()->with(Mockery::on(static function (Closure $callback) use (&$afterCommit): bool {
            $afterCommit = $callback;

            return true;
        }));
        $service->notifyRejected($id, 'Example', ResourceType::GROUP, new DraftWikiRejectionReason('Please add sources.'));
        Mail::assertNothingOutgoing();
        $this->assertInstanceOf(Closure::class, $afterCommit);
        $afterCommit();
        Mail::assertQueuedCount(1);
    }

    public function testUncommittedTransactionDoesNotLookUpOrQueue(): void
    {
        Mail::fake();
        /** @var WikiHistoryRepositoryInterface&Mockery\MockInterface $wikiHistoryRepository */
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryRepository->shouldNotReceive('findLatestSubmissionActor');
        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $principalRepository */
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        /** @var IdentityRepositoryInterface&Mockery\MockInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        DB::shouldReceive('transactionLevel')->once()->andReturn(1);
        DB::shouldReceive('afterCommit')->once()->with(Mockery::type(Closure::class));
        (new WikiWorkflowNotificationService($wikiHistoryRepository, $principalRepository, $identityRepository, 'https://frontend.example.com'))
            ->notifyPublished(new DraftWikiIdentifier(StrTestHelper::generateUuid()), 'Example', new Slug('gr-example'), Language::KOREAN);
        Mail::assertNothingOutgoing();
    }

    /** @return array{WikiWorkflowNotificationService, Identity} */
    private function service(DraftWikiIdentifier $draftWikiIdentifier, Language $language, ?string $missing = null): array
    {
        $principalId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $identity = new Identity(new IdentityIdentifier(StrTestHelper::generateUuid()), new IdentityName('Submitter'), new Email('submitter@example.com'), $language, null, new DateTimeImmutable());
        $principal = new Principal($principalId, $identity->identityIdentifier(), new AccountIdentifier(StrTestHelper::generateUuid()));
        /** @var WikiHistoryRepositoryInterface&Mockery\MockInterface $wikiHistoryRepository */
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryRepository->shouldReceive('findLatestSubmissionActor')->once()->with($draftWikiIdentifier)->andReturn($missing === 'submission' ? null : $principalId);
        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $principalRepository */
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        /** @var IdentityRepositoryInterface&Mockery\MockInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        if ($missing !== 'submission') {
            $principalRepository->shouldReceive('findById')->once()->with($principalId)->andReturn($missing === 'principal' ? null : $principal);
        }
        if ($missing !== 'submission' && $missing !== 'principal') {
            $identityRepository->shouldReceive('findById')->once()->with($identity->identityIdentifier())->andReturn($missing === 'identity' ? null : $identity);
        }

        return [new WikiWorkflowNotificationService($wikiHistoryRepository, $principalRepository, $identityRepository, 'https://frontend.example.com/'), $identity];
    }
}
