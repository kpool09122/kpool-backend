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
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Talent\Application\UseCase\Command\AutomaticCreateDraftTalent\AutomaticCreateDraftTalentInput;
use Source\Wiki\Talent\Application\UseCase\Command\AutomaticCreateDraftTalent\AutomaticCreateDraftTalentInterface;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Service\AutomaticDraftTalentCreationServiceInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\AutomaticDraftTalentCreationPayload;
use Source\Wiki\Talent\Domain\ValueObject\AutomaticDraftTalentSource;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
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
     */
    public function testProcessWithAdministrator(): void
    {
        $payload = $this->makePayload();
        $principal = $this->makePrincipal(Role::ADMINISTRATOR);
        $draftTalent = $this->makeDraftTalent();

        $service = Mockery::mock(AutomaticDraftTalentCreationServiceInterface::class);
        $service->shouldReceive('create')
            ->once()
            ->with($payload, $principal)
            ->andReturn($draftTalent);

        $repository = Mockery::mock(TalentRepositoryInterface::class);
        $repository->shouldReceive('saveDraft')
            ->once()
            ->with($draftTalent)
            ->andReturnNull();

        $this->app->instance(AutomaticDraftTalentCreationServiceInterface::class, $service);
        $this->app->instance(TalentRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftTalentInput($payload, $principal);
        $useCase = $this->app->make(AutomaticCreateDraftTalentInterface::class);

        $result = $useCase->process($input);
        $this->assertSame($draftTalent, $result);
    }

    /**
     * 正常系: ActorがSenior Collaboratorの場合、正しく自動作成されること.
     *
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $payload = $this->makePayload();
        $principal = $this->makePrincipal(Role::SENIOR_COLLABORATOR);
        $draftTalent = $this->makeDraftTalent();

        $service = Mockery::mock(AutomaticDraftTalentCreationServiceInterface::class);
        $service->shouldReceive('create')
            ->once()
            ->with($payload, $principal)
            ->andReturn($draftTalent);

        $repository = Mockery::mock(TalentRepositoryInterface::class);
        $repository->shouldReceive('saveDraft')
            ->once()
            ->with($draftTalent)
            ->andReturnNull();

        $this->app->instance(AutomaticDraftTalentCreationServiceInterface::class, $service);
        $this->app->instance(TalentRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftTalentInput($payload, $principal);
        $useCase = $this->app->make(AutomaticCreateDraftTalentInterface::class);

        $result = $useCase->process($input);
        $this->assertSame($draftTalent, $result);
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

        $service = Mockery::mock(AutomaticDraftTalentCreationServiceInterface::class);
        $repository = Mockery::mock(TalentRepositoryInterface::class);

        $this->app->instance(AutomaticDraftTalentCreationServiceInterface::class, $service);
        $this->app->instance(TalentRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftTalentInput($payload, $principal);
        $useCase = $this->app->make(AutomaticCreateDraftTalentInterface::class);

        $this->expectException(UnauthorizedException::class);
        $useCase->process($input);
    }

    private function makePayload(): AutomaticDraftTalentCreationPayload
    {
        return new AutomaticDraftTalentCreationPayload(
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Language::KOREAN,
            new TalentName('テストタレント'),
            new RealName('Test Talent'),
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            [
                new GroupIdentifier(StrTestHelper::generateUlid()),
            ],
            new Birthday(new DateTimeImmutable('1998-02-10')),
            new Career('Auto generated career'),
            new AutomaticDraftTalentSource('news::auto-talents'),
        );
    }

    private function makePrincipal(Role $role): Principal
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());

        return new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), $role, null, [], []);
    }

    /**
     * @return DraftTalent
     * @throws ExceedMaxRelevantVideoLinksException
     */
    private function makeDraftTalent(): DraftTalent
    {
        return new DraftTalent(
            new TalentIdentifier(StrTestHelper::generateUlid()),
            null,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Language::KOREAN,
            new TalentName('テストタレント'),
            new RealName('Test Talent'),
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
