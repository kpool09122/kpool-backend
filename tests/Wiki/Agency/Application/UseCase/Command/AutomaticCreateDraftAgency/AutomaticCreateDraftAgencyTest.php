<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\AutomaticCreateDraftAgency;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Application\UseCase\Command\AutomaticCreateDraftAgency\AutomaticCreateDraftAgencyInput;
use Source\Wiki\Agency\Application\UseCase\Command\AutomaticCreateDraftAgency\AutomaticCreateDraftAgencyInterface;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\Service\AutomaticDraftAgencyCreationServiceInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\AutomaticDraftAgencyCreationPayload;
use Source\Wiki\Agency\Domain\ValueObject\AutomaticDraftAgencySource;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AutomaticCreateDraftAgencyTest extends TestCase
{
    /**
     * 正常系: ActorがAdministratorの場合、正しく自動作成されること.
     *
     * @throws BindingResolutionException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAdministrator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $payload = $this->makePayload();
        $draftAgency = $this->makeDraftAgency();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutomaticDraftAgencyCreationServiceInterface::class);
        $service->shouldReceive('create')
            ->once()
            ->with($payload, $principal)
            ->andReturn($draftAgency);

        $repository = Mockery::mock(AgencyRepositoryInterface::class);
        $repository->shouldReceive('saveDraft')
            ->once()
            ->with($draftAgency)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftAgencyCreationServiceInterface::class, $service);
        $this->app->instance(AgencyRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftAgencyInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutomaticCreateDraftAgencyInterface::class);

        $result = $useCase->process($input);
        $this->assertSame($draftAgency, $result);
    }

    /**
     * 正常系: ActorがSenior Collaboratorの場合、正しく自動作成されること.
     *
     * @throws BindingResolutionException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::SENIOR_COLLABORATOR, null, [], []);

        $payload = $this->makePayload();
        $draftAgency = $this->makeDraftAgency();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutomaticDraftAgencyCreationServiceInterface::class);
        $service->shouldReceive('create')
            ->once()
            ->with($payload, $principal)
            ->andReturn($draftAgency);

        $repository = Mockery::mock(AgencyRepositoryInterface::class);
        $repository->shouldReceive('saveDraft')
            ->once()
            ->with($draftAgency)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftAgencyCreationServiceInterface::class, $service);
        $this->app->instance(AgencyRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftAgencyInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutomaticCreateDraftAgencyInterface::class);

        $result = $useCase->process($input);
        $this->assertSame($draftAgency, $result);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());

        $payload = $this->makePayload();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $service = Mockery::mock(AutomaticDraftAgencyCreationServiceInterface::class);
        $repository = Mockery::mock(AgencyRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftAgencyCreationServiceInterface::class, $service);
        $this->app->instance(AgencyRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftAgencyInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutomaticCreateDraftAgencyInterface::class);

        $this->expectException(PrincipalNotFoundException::class);
        $useCase->process($input);
    }

    /**
     * 異常系: ActorがAdministratorかSenior Collaboratorでない場合は、例外がスローされること.
     *
     * @throws BindingResolutionException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithUnauthorizedRole(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::AGENCY_ACTOR, null, [], []);

        $payload = $this->makePayload();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutomaticDraftAgencyCreationServiceInterface::class);
        $repository = Mockery::mock(AgencyRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftAgencyCreationServiceInterface::class, $service);
        $this->app->instance(AgencyRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftAgencyInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutomaticCreateDraftAgencyInterface::class);

        $this->expectException(UnauthorizedException::class);
        $useCase->process($input);
    }

    private function makePayload(): AutomaticDraftAgencyCreationPayload
    {
        return new AutomaticDraftAgencyCreationPayload(
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Language::KOREAN,
            new AgencyName('JYP엔터테인먼트'),
            new CEO('J.Y. Park'),
            new FoundedIn(new DateTimeImmutable('1997-04-25')),
            new Description('### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악プロデューサー인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다.'),
            new AutomaticDraftAgencySource('news::12345'),
        );
    }

    private function makeDraftAgency(): DraftAgency
    {
        return new DraftAgency(
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            null,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Language::KOREAN,
            new AgencyName('JYP엔터테인먼트'),
            'JYPㅇㅌㅌㅇㅁㅌ',
            new CEO('J.Y. Park'),
            'j.y. park',
            new FoundedIn(new DateTimeImmutable('1997-04-25')),
            new Description('auto generated'),
            ApprovalStatus::Pending,
        );
    }
}
