<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\AutomaticCreateDraftTalent;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Application\UseCase\Command\AutomaticCreateDraftTalent\AutomaticCreateDraftTalentInput;
use Source\Wiki\Talent\Application\UseCase\Command\AutomaticCreateDraftTalent\AutomaticCreateDraftTalentInterface;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Repository\DraftTalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Service\AutomaticDraftTalentCreationServiceInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\AutomaticDraftTalentCreationPayload;
use Source\Wiki\Talent\Domain\ValueObject\AutomaticDraftTalentSource;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AutomaticCreateDraftTalentTest extends TestCase
{
    /**
     * 正常系: ActorがAdministratorの場合、正しく自動作成されること.
     *
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAdministrator(): void
    {
        $payload = $this->makePayload();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = $this->makePrincipal($principalIdentifier);
        $draftTalent = $this->makeDraftTalent();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutomaticDraftTalentCreationServiceInterface::class);
        $service->shouldReceive('create')
            ->once()
            ->with($payload, $principal)
            ->andReturn($draftTalent);

        $repository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->with($draftTalent)
            ->andReturnNull();

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftTalentCreationServiceInterface::class, $service);
        $this->app->instance(DraftTalentRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftTalentInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutomaticCreateDraftTalentInterface::class);

        $result = $useCase->process($input);
        $this->assertSame($draftTalent, $result);
    }

    /**
     * 正常系: ActorがSenior Collaboratorの場合、正しく自動作成されること.
     *
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $payload = $this->makePayload();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = $this->makePrincipal($principalIdentifier);
        $draftTalent = $this->makeDraftTalent();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutomaticDraftTalentCreationServiceInterface::class);
        $service->shouldReceive('create')
            ->once()
            ->with($payload, $principal)
            ->andReturn($draftTalent);

        $repository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->with($draftTalent)
            ->andReturnNull();

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftTalentCreationServiceInterface::class, $service);
        $this->app->instance(DraftTalentRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftTalentInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutomaticCreateDraftTalentInterface::class);

        $result = $useCase->process($input);
        $this->assertSame($draftTalent, $result);
    }

    /**
     * 異常系: ActorがAdministratorかSenior Collaboratorでない場合は、例外がスローされること.
     *
     * @throws BindingResolutionException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithUnauthorizedRole(): void
    {
        $payload = $this->makePayload();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = $this->makePrincipal($principalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutomaticDraftTalentCreationServiceInterface::class);
        $repository = Mockery::mock(DraftTalentRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftTalentCreationServiceInterface::class, $service);
        $this->app->instance(DraftTalentRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftTalentInput($payload, $principalIdentifier);
        $this->setPolicyEvaluatorResult(false);
        $useCase = $this->app->make(AutomaticCreateDraftTalentInterface::class);

        $this->expectException(UnauthorizedException::class);
        $useCase->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $payload = $this->makePayload();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $service = Mockery::mock(AutomaticDraftTalentCreationServiceInterface::class);
        $repository = Mockery::mock(DraftTalentRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftTalentCreationServiceInterface::class, $service);
        $this->app->instance(DraftTalentRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftTalentInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutomaticCreateDraftTalentInterface::class);

        $this->expectException(PrincipalNotFoundException::class);
        $useCase->process($input);
    }

    private function makePayload(): AutomaticDraftTalentCreationPayload
    {
        return new AutomaticDraftTalentCreationPayload(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new TalentName('テストタレント'),
            new RealName('Test Talent'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [
                new GroupIdentifier(StrTestHelper::generateUuid()),
            ],
            new Birthday(new DateTimeImmutable('1998-02-10')),
            new Career('Auto generated career'),
            new AutomaticDraftTalentSource('news::auto-talents'),
        );
    }

    private function makePrincipal(PrincipalIdentifier $principalIdentifier): Principal
    {
        return new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
    }

    /**
     * @return DraftTalent
     * @throws ExceedMaxRelevantVideoLinksException
     */
    private function makeDraftTalent(): DraftTalent
    {
        return new DraftTalent(
            new TalentIdentifier(StrTestHelper::generateUuid()),
            null,
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new TalentName('テストタレント'),
            'テストタレント',
            new RealName('Test Talent'),
            'test talent',
            null,
            [],
            null,
            new Career('auto generated'),
            null,
            new RelevantVideoLinks([]),
            ApprovalStatus::Pending,
        );
    }
}
