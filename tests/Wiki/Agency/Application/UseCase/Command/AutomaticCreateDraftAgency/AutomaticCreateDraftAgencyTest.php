<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\AutomaticCreateDraftAgency;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\Translation;
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
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AutomaticCreateDraftAgencyTest extends TestCase
{
    /**
     * 正常系: ActorがAdministratorの場合、正しく自動作成されること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessWithAdministrator(): void
    {
        $payload = $this->makePayload();
        $principal = $this->makePrincipal(Role::ADMINISTRATOR);
        $draftAgency = $this->makeDraftAgency();

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

        $this->app->instance(AutomaticDraftAgencyCreationServiceInterface::class, $service);
        $this->app->instance(AgencyRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftAgencyInput($payload, $principal);
        $useCase = $this->app->make(AutomaticCreateDraftAgencyInterface::class);

        $result = $useCase->process($input);
        $this->assertSame($draftAgency, $result);
    }

    /**
     * 正常系: ActorがSenior Collaboratorの場合、正しく自動作成されること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $payload = $this->makePayload();
        $principal = $this->makePrincipal(Role::SENIOR_COLLABORATOR);
        $draftAgency = $this->makeDraftAgency();

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

        $this->app->instance(AutomaticDraftAgencyCreationServiceInterface::class, $service);
        $this->app->instance(AgencyRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftAgencyInput($payload, $principal);
        $useCase = $this->app->make(AutomaticCreateDraftAgencyInterface::class);

        $result = $useCase->process($input);
        $this->assertSame($draftAgency, $result);
    }

    /**
     * 異常系: ActorがAdministratorかSenior Collaboratorでない場合は、例外がスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessWithUnauthorizedRole(): void
    {
        $payload = $this->makePayload();
        $principal = $this->makePrincipal(Role::AGENCY_ACTOR);

        $service = Mockery::mock(AutomaticDraftAgencyCreationServiceInterface::class);
        $repository = Mockery::mock(AgencyRepositoryInterface::class);

        $this->app->instance(AutomaticDraftAgencyCreationServiceInterface::class, $service);
        $this->app->instance(AgencyRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftAgencyInput($payload, $principal);
        $useCase = $this->app->make(AutomaticCreateDraftAgencyInterface::class);

        $this->expectException(UnauthorizedException::class);
        $useCase->process($input);
    }

    private function makePayload(): AutomaticDraftAgencyCreationPayload
    {
        return new AutomaticDraftAgencyCreationPayload(
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Translation::KOREAN,
            new AgencyName('JYP엔터테인먼트'),
            new CEO('J.Y. Park'),
            new FoundedIn(new DateTimeImmutable('1997-04-25')),
            new Description('### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악プロデューサー인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다.'),
            new AutomaticDraftAgencySource('news::12345'),
        );
    }

    private function makePrincipal(Role $role): Principal
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());

        return new Principal($principalIdentifier, $role, null, [], []);
    }

    private function makeDraftAgency(): DraftAgency
    {
        return new DraftAgency(
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            null,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Translation::KOREAN,
            new AgencyName('JYP엔터テインメント'),
            new CEO('J.Y. Park'),
            new FoundedIn(new DateTimeImmutable('1997-04-25')),
            new Description('auto generated'),
            ApprovalStatus::Pending,
        );
    }
}
