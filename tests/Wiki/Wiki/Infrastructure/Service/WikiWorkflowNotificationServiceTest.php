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
use Source\Wiki\Wiki\Domain\Event\WikiApproved;
use Source\Wiki\Wiki\Domain\Event\WikiPublished;
use Source\Wiki\Wiki\Domain\Event\WikiRejected;
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

    public function testSendsEachOutcomeOnlyToSubmissionActorInTheirLanguage(): void
    {
        foreach (Language::cases() as $language) {
            foreach ($this->events() as $event) {
                Mail::fake();
                [$service, $submitter] = $this->service($event->draftWikiIdentifier, $language);
                $service->notify($event);
                $type = match (true) {
                    $event instanceof WikiApproved => WikiWorkflowMailType::APPROVED,
                    $event instanceof WikiRejected => WikiWorkflowMailType::REJECTED,
                    $event instanceof WikiPublished => WikiWorkflowMailType::PUBLISHED,
                };
                $url = match (true) {
                    $event instanceof WikiApproved => null,
                    $event instanceof WikiPublished => 'https://frontend.example.com/ko/wiki/gr-example',
                    $event instanceof WikiRejected => 'https://frontend.example.com/wiki/' . $event->draftWikiIdentifier . '/edit?resourceType=group',
                };
                Mail::assertSent(WikiWorkflowMail::class, static fn (WikiWorkflowMail $mail): bool => $mail->hasTo((string) $submitter->email())
                    && $mail->language === $language && $mail->type === $type && $mail->wikiName === $event->wikiName
                    && $mail->wikiUrl === $url
                    && $mail->rejectionReason === ($event instanceof WikiRejected ? (string) $event->rejectionReason : null));
                Mail::assertSentCount(1);
            }
        }
    }

    public function testDoesNotSendForMissingSubmissionPrincipalOrIdentity(): void
    {
        foreach (['submission', 'principal', 'identity'] as $missing) {
            Mail::fake();
            $event = $this->events()[0];
            [$service] = $this->service($event->draftWikiIdentifier, Language::JAPANESE, $missing);
            $service->notify($event);
            Mail::assertNothingSent();
        }
    }

    public function testDefersRecipientLookupAndDeliveryUntilCommit(): void
    {
        Mail::fake();
        $event = $this->events()[1];
        [$service] = $this->service($event->draftWikiIdentifier, Language::JAPANESE);
        $afterCommit = null;
        DB::shouldReceive('transactionLevel')->once()->andReturn(1);
        DB::shouldReceive('afterCommit')->once()->with(Mockery::on(static function (Closure $callback) use (&$afterCommit): bool {
            $afterCommit = $callback;

            return true;
        }));
        $service->notify($event);
        Mail::assertNothingSent();
        $this->assertInstanceOf(Closure::class, $afterCommit);
        $afterCommit();
        Mail::assertSentCount(1);
    }

    public function testUncommittedTransactionDoesNotLookUpOrSend(): void
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
        (new WikiWorkflowNotificationService($wikiHistoryRepository, $principalRepository, $identityRepository, 'https://frontend.example.com'))->notify($this->events()[2]);
        Mail::assertNothingSent();
    }

    /** @return list<WikiApproved|WikiRejected|WikiPublished> */
    private function events(): array
    {
        $id = new DraftWikiIdentifier(StrTestHelper::generateUuid());

        return [
            new WikiApproved($id, 'Example', ResourceType::GROUP),
            new WikiRejected($id, 'Example', ResourceType::GROUP, new DraftWikiRejectionReason('Please add sources.')),
            new WikiPublished($id, 'Example', ResourceType::GROUP, new Slug('gr-example'), Language::KOREAN),
        ];
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
