<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\TranslateTalent;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Application\Service\TranslationServiceInterface;
use Source\Wiki\Talent\Application\UseCase\Command\TranslateTalent\TranslateTalent;
use Source\Wiki\Talent\Application\UseCase\Command\TranslateTalent\TranslateTalentInput;
use Source\Wiki\Talent\Application\UseCase\Command\TranslateTalent\TranslateTalentInterface;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateTalentTest extends TestCase
{
    /**
     * 正常系：DIが正しく動作すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $this->assertInstanceOf(TranslateTalent::class, $translateTalent);
    }

    /**
     * 正常系：正しく他の言語に翻訳されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $input = new TranslateTalentInput(
            $translateTalentInfo->talentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->with($translateTalentInfo->talentIdentifier)
            ->once()
            ->andReturn($translateTalentInfo->talent);
        $talentRepository->shouldReceive('saveDraft')
            ->with($translateTalentInfo->enTalent)
            ->once()
            ->andReturn(null);
        $talentRepository->shouldReceive('saveDraft')
            ->with($translateTalentInfo->jaTalent)
            ->once()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateTalent')
            ->with($translateTalentInfo->talent, $translateTalentInfo->english)
            ->once()
            ->andReturn($translateTalentInfo->enTalent);
        $translationService->shouldReceive('translateTalent')
            ->with($translateTalentInfo->talent, $translateTalentInfo->japanese)
            ->once()
            ->andReturn($translateTalentInfo->jaTalent);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $talents = $translateTalent->process($input);
        $this->assertCount(2, $talents);
        $this->assertSame($translateTalentInfo->jaTalent, $talents[0]);
        $this->assertSame($translateTalentInfo->enTalent, $talents[1]);
    }

    /**
     * 異常系： 指定したIDのメンバー情報が見つからない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testWhenTalentNotFound(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new TranslateTalentInput(
            $translateTalentInfo->talentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->with($translateTalentInfo->talentIdentifier)
            ->once()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $this->expectException(TalentNotFoundException::class);
        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $translateTalent->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new TranslateTalentInput(
            $translateTalentInfo->talentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->with($translateTalentInfo->talentIdentifier)
            ->once()
            ->andReturn($translateTalentInfo->talent);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $this->expectException(PrincipalNotFoundException::class);
        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $translateTalent->process($input);
    }

    /**
     * 異常系：承認権限がないロール（Collaborator）の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedRole(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::COLLABORATOR, null, [], []);

        $input = new TranslateTalentInput(
            $translateTalentInfo->talentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($translateTalentInfo->talentIdentifier)
            ->andReturn($translateTalentInfo->talent);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);

        $this->expectException(UnauthorizedException::class);
        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $translateTalent->process($input);
    }

    /**
     * 正常系：ADMINISTRATORがメンバーを翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAdministrator(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $input = new TranslateTalentInput(
            $translateTalentInfo->talentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($translateTalentInfo->talentIdentifier)
            ->andReturn($translateTalentInfo->talent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($translateTalentInfo->enTalent)
            ->andReturn(null);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($translateTalentInfo->jaTalent)
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateTalent')
            ->once()
            ->with($translateTalentInfo->talent, $translateTalentInfo->english)
            ->andReturn($translateTalentInfo->enTalent);
        $translationService->shouldReceive('translateTalent')
            ->once()
            ->with($translateTalentInfo->talent, $translateTalentInfo->japanese)
            ->andReturn($translateTalentInfo->jaTalent);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);

        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $talents = $translateTalent->process($input);

        $this->assertCount(2, $talents);
        $this->assertSame($translateTalentInfo->jaTalent, $talents[0]);
        $this->assertSame($translateTalentInfo->enTalent, $talents[1]);
    }

    /**
     * 異常系：AGENCY_ACTORが自分の所属していないグループのメンバーを翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedAgencyScope(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $anotherAgencyId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::AGENCY_ACTOR, $anotherAgencyId, [], []);

        $input = new TranslateTalentInput(
            $translateTalentInfo->talentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($translateTalentInfo->talentIdentifier)
            ->andReturn($translateTalentInfo->talent);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);

        $this->expectException(UnauthorizedException::class);
        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $translateTalent->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORが自分の所属するグループのメンバーを翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedAgencyActor(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = (string) $translateTalentInfo->agencyIdentifier;
        $groupIds = array_map(static fn ($groupId) => (string)$groupId, $translateTalentInfo->groupIdentifiers);
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::AGENCY_ACTOR, $agencyId, $groupIds, []);

        $input = new TranslateTalentInput(
            $translateTalentInfo->talentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($translateTalentInfo->talentIdentifier)
            ->andReturn($translateTalentInfo->talent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($translateTalentInfo->enTalent)
            ->andReturn(null);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($translateTalentInfo->jaTalent)
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateTalent')
            ->once()
            ->with($translateTalentInfo->talent, $translateTalentInfo->english)
            ->andReturn($translateTalentInfo->enTalent);
        $translationService->shouldReceive('translateTalent')
            ->once()
            ->with($translateTalentInfo->talent, $translateTalentInfo->japanese)
            ->andReturn($translateTalentInfo->jaTalent);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);

        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $talents = $translateTalent->process($input);

        $this->assertCount(2, $talents);
        $this->assertSame($translateTalentInfo->jaTalent, $talents[0]);
        $this->assertSame($translateTalentInfo->enTalent, $talents[1]);
    }

    /**
     * 異常系：GROUP_ACTORが自分の所属していないグループのメンバーを翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedGroupScope(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = (string) $translateTalentInfo->agencyIdentifier;
        $anotherGroupId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::GROUP_ACTOR, $agencyId, [$anotherGroupId], []);

        $input = new TranslateTalentInput(
            $translateTalentInfo->talentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($translateTalentInfo->talentIdentifier)
            ->andReturn($translateTalentInfo->talent);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);

        $this->expectException(UnauthorizedException::class);
        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $translateTalent->process($input);
    }

    /**
     * 正常系：GROUP_ACTORが自分の所属するグループのメンバーを翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedGroupActor(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = (string) $translateTalentInfo->agencyIdentifier;
        $groupIds = array_map(static fn ($groupId) => (string)$groupId, $translateTalentInfo->groupIdentifiers);
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::GROUP_ACTOR, $agencyId, $groupIds, []);

        $input = new TranslateTalentInput(
            $translateTalentInfo->talentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($translateTalentInfo->talentIdentifier)
            ->andReturn($translateTalentInfo->talent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($translateTalentInfo->enTalent)
            ->andReturn(null);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($translateTalentInfo->jaTalent)
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateTalent')
            ->once()
            ->with($translateTalentInfo->talent, $translateTalentInfo->english)
            ->andReturn($translateTalentInfo->enTalent);
        $translationService->shouldReceive('translateTalent')
            ->once()
            ->with($translateTalentInfo->talent, $translateTalentInfo->japanese)
            ->andReturn($translateTalentInfo->jaTalent);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);

        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $talents = $translateTalent->process($input);

        $this->assertCount(2, $talents);
        $this->assertSame($translateTalentInfo->jaTalent, $talents[0]);
        $this->assertSame($translateTalentInfo->enTalent, $talents[1]);
    }

    /**
     * 異常系：TALENT_ACTORが自分の所属していないグループのメンバーを翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedTalentScope(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = (string) $translateTalentInfo->agencyIdentifier;
        $anotherGroupId = StrTestHelper::generateUuid();
        $anotherTalentId = StrTestHelper::generateUuid(); // 別のTalent IDを使用
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::TALENT_ACTOR, $agencyId, [$anotherGroupId], [$anotherTalentId]);

        $input = new TranslateTalentInput(
            $translateTalentInfo->talentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($translateTalentInfo->talentIdentifier)
            ->andReturn($translateTalentInfo->talent);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);

        $this->expectException(UnauthorizedException::class);
        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $translateTalent->process($input);
    }

    /**
     * 正常系：TALENT_ACTORが自分の所属するグループのメンバーを翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedTalentActor(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = (string) $translateTalentInfo->agencyIdentifier;
        $groupIds = array_map(static fn ($groupId) => (string)$groupId, $translateTalentInfo->groupIdentifiers);
        $talentId = (string) $translateTalentInfo->talentIdentifier;
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::TALENT_ACTOR, $agencyId, $groupIds, [$talentId]);

        $input = new TranslateTalentInput(
            $translateTalentInfo->talentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($translateTalentInfo->talentIdentifier)
            ->andReturn($translateTalentInfo->talent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($translateTalentInfo->enTalent)
            ->andReturn(null);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($translateTalentInfo->jaTalent)
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateTalent')
            ->once()
            ->with($translateTalentInfo->talent, $translateTalentInfo->english)
            ->andReturn($translateTalentInfo->enTalent);
        $translationService->shouldReceive('translateTalent')
            ->once()
            ->with($translateTalentInfo->talent, $translateTalentInfo->japanese)
            ->andReturn($translateTalentInfo->jaTalent);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);

        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $talents = $translateTalent->process($input);

        $this->assertCount(2, $talents);
        $this->assertSame($translateTalentInfo->jaTalent, $talents[0]);
        $this->assertSame($translateTalentInfo->enTalent, $talents[1]);
    }

    /**
     * 正常系：SENIOR_COLLABORATORがメンバーを翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::SENIOR_COLLABORATOR, null, [], []);

        $input = new TranslateTalentInput(
            $translateTalentInfo->talentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($translateTalentInfo->talentIdentifier)
            ->andReturn($translateTalentInfo->talent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($translateTalentInfo->enTalent)
            ->andReturn(null);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($translateTalentInfo->jaTalent)
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateTalent')
            ->once()
            ->with($translateTalentInfo->talent, $translateTalentInfo->english)
            ->andReturn($translateTalentInfo->enTalent);
        $translationService->shouldReceive('translateTalent')
            ->once()
            ->with($translateTalentInfo->talent, $translateTalentInfo->japanese)
            ->andReturn($translateTalentInfo->jaTalent);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);

        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $talents = $translateTalent->process($input);

        $this->assertCount(2, $talents);
        $this->assertSame($translateTalentInfo->jaTalent, $talents[0]);
        $this->assertSame($translateTalentInfo->enTalent, $talents[1]);
    }

    /**
     * 異常系：NONEロールがメンバーを翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedNoneRole(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::NONE, null, [], []);

        $input = new TranslateTalentInput(
            $translateTalentInfo->talentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($translateTalentInfo->talentIdentifier)
            ->andReturn($translateTalentInfo->talent);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);

        $this->expectException(UnauthorizedException::class);
        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $translateTalent->process($input);
    }

    /**
     * @return TranslateTalentTestData
     * @throws ExceedMaxRelevantVideoLinksException
     */
    private function createTranslateTalentInfo(): TranslateTalentTestData
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new GroupIdentifier(StrTestHelper::generateUuid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1999-04-23'));
        $career = new Career('손채영은 대한민국의 걸그룹 트와이스의 멤버입니다. 트와이스에서 메인래퍼와 서브보컬을 담당하고 있으며, 작사, 작곡에도 참여하며 다재다능한 아티스트로서의 면모를 보여주고 있습니다.
**데뷔 전**
어린 시절부터 춤에 재능을 보였던 채영은 2012년 JYP 엔터테인먼트 오디션에 합격하여 연습생 생활을 시작했습니다. 약 3년간의 연습생 기간을 거치며 랩과 보컬 실력을 갈고닦았고, 데뷔 전 GOT7의 "하지하지마" 뮤직비디오에 출연하기도 했습니다.
**SIXTEEN과 트와이스 데뷔**
2015년, JYP의 신인 걸그룹 트와이스의 멤버를 선발하는 서바이벌 프로그램 Mnet \'SIXTEEN\'에 참가하여 개성 있는 랩과 무대 매너로 주목을 받았습니다. 최종 멤버로 발탁되어 2015년 10월 20일, 트와이스의 첫 번째 미니 앨범 "THE STORY BEGINS"로 정식 데뷔했습니다.
**트와이스 활동 및 솔로 활동**
트와이스의 멤버로서 채영은 수많은 히트곡에 참여하며 전 세계적인 인기를 얻는 데 기여했습니다. 그룹 내에서 독특한 음색과 안정적인 랩 실력으로 곡의 매력을 더하고 있습니다.
또한, 다수의 트와이스 앨범 수록곡 작사에 참여하며 꾸준히 음악적 역량을 키워왔습니다. "PAGE TWO" 앨범의 "소중한 사랑" 랩 메이킹을 시작으로, "LIKEY", "What is Love?", "Feel Special" 등 다수의 곡 작업에 이름을 올렸습니다.
최근에는 솔로 아티스트로서의 활동도 시작하며 음악적 스펙트럼을 넓혀가고 있습니다.');
        $imagePath = new ImagePath('/resources/public/images/before.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $externalContentLinks = [$link1, $link2, $link3];
        $relevantVideoLinks = new RelevantVideoLinks($externalContentLinks);

        $imageLink = new ImagePath('/resources/public/images/before.webp');

        $status = ApprovalStatus::UnderReview;
        $version = new Version(1);
        $talent = new Talent(
            $talentIdentifier,
            $translationSetIdentifier,
            $language,
            $name,
            $realName,
            $agencyIdentifier,
            $groupIdentifiers,
            $birthday,
            $career,
            $imageLink,
            $relevantVideoLinks,
            $version,
        );

        $jaTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $japanese = Language::JAPANESE;
        $jaName = new TalentName('チェヨン');
        $jaRealName = new RealName('ソン・チェヨン');
        $jaAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $jaGroupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new GroupIdentifier(StrTestHelper::generateUuid()),
        ];
        $jaBirthday = new Birthday(new DateTimeImmutable('1999-04-23'));
        $jaCareer = new Career('ソン・チェヨンは、韓国のガールズグループTWICEのメンバーです。TWICEではメインラッパーとサブボーカルを担当しており、作詞・作曲にも参加し、多才なアーティストとしての一面を見せています。
**デビュー前**
幼い頃からダンスに才能を見せていたチェヨンは、2012年にJYPエンターテインメントのオーディションに合格し、練習生生活を始めました。約3年間の練習生期間を経てラップとボーカルの実力を磨き、デビュー前にはGOT7の「Stop stop it」のミュージックビデオに出演したこともあります。
**SIXTEENとTWICEでのデビュー**
2015年、JYPの新人ガールズグループTWICEのメンバーを選抜するサバイバル番組Mnet「SIXTEEN」に参加し、個性的なラップとステージマナーで注目を集めました。最終メンバーに抜擢され、2015年10月20日、TWICEの1stミニアルバム「THE STORY BEGINS」で正式にデビューしました。
**TWICEでの活動とソロ活動**
TWICEのメンバーとして、チェヨンは数多くのヒット曲に参加し、世界的な人気を得ることに貢献しました。グループ内では独特な歌声と安定したラップの実力で、楽曲の魅力を一層高めています。
また、多数のTWICEのアルバム収録曲の作詞に参加し、着実に音楽的な実力を伸ばしてきました。「PAGE TWO」収録の「Precious Love」のラップメイキングを皮切りに、「LIKEY」、「What is Love?」、「Feel Special」など、多数の楽曲制作に名を連ねています。
最近ではソロアーティストとしての活動も開始し、音楽の幅を広げています。');
        $jaImagePath = new ImagePath('/resources/public/images/after1.webp');
        $link4 = new ExternalContentLink('https://example4.youtube.com/watch?v=dQw4w9WgXcQ');
        $externalContentLinks2 = [$link4];
        $jaRelevantVideoLinks = new RelevantVideoLinks($externalContentLinks2);
        $jaTalent = new DraftTalent(
            $jaTalentIdentifier,
            $talentIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $japanese,
            $jaName,
            $jaRealName,
            $jaAgencyIdentifier,
            $jaGroupIdentifiers,
            $jaBirthday,
            $jaCareer,
            $jaImagePath,
            $jaRelevantVideoLinks,
            ApprovalStatus::Pending,
        );

        $enTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $english = Language::ENGLISH;
        $enName = new TalentName('Chae-young');
        $enRealName = new RealName('Son Chae-young');
        $enAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $enGroupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new GroupIdentifier(StrTestHelper::generateUuid()),
        ];
        $enBirthday = new Birthday(new DateTimeImmutable('1999-04-23'));
        $enCareer = new Career('Son Chaeyoung is a talent of the South Korean girl group TWICE. In the group, she serves as the main rapper and a sub-vocalist, and she has also shown her versatility as a multi-talented artist by participating in lyric writing and composition.
**Pre-Debut**
Showing a talent for dance from a young age, Chaeyoung passed the JYP Entertainment audition in 2012 and began her life as a trainee. Over a training period of about three years, she polished her rap and vocal skills. Before her debut, she also appeared in the music video for GOT7\'s "Stop stop it."
**SIXTEEN and Debut with TWICE**
In 2015, she participated in Mnet\'s survival show "SIXTEEN," a program designed to select the talents for JYP\'s new girl group, TWICE. She gained attention for her unique rapping style and stage presence. She was selected as a final talent and officially debuted on October 20, 2015, with TWICE\'s first mini-album, "THE STORY BEGINS."
**Activities with TWICE and Solo Career**
As a talent of TWICE, Chaeyoung has contributed to the group\'s global popularity through numerous hit songs. Within the group, she enhances their music with her unique vocal tone and stable rapping skills.
Furthermore, she has consistently developed her musical abilities by writing lyrics for many of TWICE\'s album tracks. Starting with making the rap for "Precious Love" from the "PAGE TWO" album, she has been credited on numerous songs, including "LIKEY," "What is Love?," and "Feel Special."
Recently, she has also begun activities as a solo artist, further broadening her musical spectrum.');
        $enImagePath = new ImagePath('/resources/public/images/after2.webp');
        $link5 = new ExternalContentLink('https://example5.youtube.com/watch?v=dQw4w9WgXcQ');
        $externalContentLinks3 = [$link5];
        $enRelevantVideoLinks = new RelevantVideoLinks($externalContentLinks3);
        $enTalent = new DraftTalent(
            $enTalentIdentifier,
            $talentIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $english,
            $enName,
            $enRealName,
            $enAgencyIdentifier,
            $enGroupIdentifiers,
            $enBirthday,
            $enCareer,
            $enImagePath,
            $enRelevantVideoLinks,
            ApprovalStatus::Pending,
        );

        return new TranslateTalentTestData(
            $talentIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $realName,
            $agencyIdentifier,
            $groupIdentifiers,
            $birthday,
            $career,
            $link1,
            $link2,
            $link3,
            $relevantVideoLinks,
            $imageLink,
            $status,
            $talent,
            $english,
            $enTalent,
            $japanese,
            $jaTalent,
        );
    }
}


/**
 * テストデータを保持するクラス
 */
readonly class TranslateTalentTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     * @param GroupIdentifier[] $groupIdentifiers
     */
    public function __construct(
        public TalentIdentifier         $talentIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public EditorIdentifier         $editorIdentifier,
        public Language                 $language,
        public TalentName               $name,
        public RealName                 $realName,
        public AgencyIdentifier         $agencyIdentifier,
        public array                    $groupIdentifiers,
        public Birthday                 $birthday,
        public Career                   $career,
        public ExternalContentLink      $link1,
        public ExternalContentLink      $link2,
        public ExternalContentLink      $link3,
        public RelevantVideoLinks       $relevantVideoLinks,
        public ImagePath                $imageLink,
        public ApprovalStatus           $status,
        public Talent                   $talent,
        public Language                 $english,
        public DraftTalent              $enTalent,
        public Language                 $japanese,
        public DraftTalent              $jaTalent,
    ) {
    }
}
