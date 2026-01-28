<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\TranslateTalent;

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
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Application\Service\TranslatedTalentData;
use Source\Wiki\Talent\Application\Service\TranslationServiceInterface;
use Source\Wiki\Talent\Application\UseCase\Command\TranslateTalent\TranslateTalent;
use Source\Wiki\Talent\Application\UseCase\Command\TranslateTalent\TranslateTalentInput;
use Source\Wiki\Talent\Application\UseCase\Command\TranslateTalent\TranslateTalentInterface;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Factory\DraftTalentFactoryInterface;
use Source\Wiki\Talent\Domain\Repository\DraftTalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
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
        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $draftTalentFactory = Mockery::mock(DraftTalentFactoryInterface::class);
        $this->app->instance(DraftTalentFactoryInterface::class, $draftTalentFactory);
        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $this->assertInstanceOf(TranslateTalent::class, $translateTalent);
    }

    /**
     * 正常系：正しく他の言語に翻訳されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

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

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateTalent')
            ->with($translateTalentInfo->talent, Language::JAPANESE)
            ->once()
            ->andReturn(new TranslatedTalentData(
                translatedName: 'チェヨン',
                translatedRealName: 'ソン・チェヨン',
                translatedCareer: '### チェヨンはTWICEのメンバーです。',
            ));
        $translationService->shouldReceive('translateTalent')
            ->with($translateTalentInfo->talent, Language::ENGLISH)
            ->once()
            ->andReturn(new TranslatedTalentData(
                translatedName: 'Chaeyoung',
                translatedRealName: 'Son Chaeyoung',
                translatedCareer: '### Chaeyoung is a member of TWICE.',
            ));

        $draftTalentFactory = Mockery::mock(DraftTalentFactoryInterface::class);
        $draftTalentFactory->shouldReceive('create')
            ->with(
                Mockery::on(fn ($arg) => $arg === null),
                $translateTalentInfo->talent->slug(),
                Language::JAPANESE,
                Mockery::on(fn (TalentName $name) => (string) $name === 'チェヨン'),
                $translateTalentInfo->talent->translationSetIdentifier(),
            )
            ->once()
            ->andReturn($translateTalentInfo->jaTalent);
        $draftTalentFactory->shouldReceive('create')
            ->with(
                Mockery::on(fn ($arg) => $arg === null),
                $translateTalentInfo->talent->slug(),
                Language::ENGLISH,
                Mockery::on(fn (TalentName $name) => (string) $name === 'Chaeyoung'),
                $translateTalentInfo->talent->translationSetIdentifier(),
            )
            ->once()
            ->andReturn($translateTalentInfo->enTalent);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(DraftTalentFactoryInterface::class, $draftTalentFactory);
        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $talents = $translateTalent->process($input);
        $this->assertCount(2, $talents);
    }

    /**
     * 異常系： 指定したIDのメンバー情報が見つからない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
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
        $draftTalentFactory = Mockery::mock(DraftTalentFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(DraftTalentFactoryInterface::class, $draftTalentFactory);

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
        $draftTalentFactory = Mockery::mock(DraftTalentFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(DraftTalentFactoryInterface::class, $draftTalentFactory);

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
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedRole(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

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
        $draftTalentFactory = Mockery::mock(DraftTalentFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(DraftTalentFactoryInterface::class, $draftTalentFactory);

        $this->setPolicyEvaluatorResult(false);

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
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAdministrator(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

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

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateTalent')
            ->twice()
            ->andReturn(new TranslatedTalentData(
                translatedName: 'Chaeyoung',
                translatedRealName: 'Son Chaeyoung',
                translatedCareer: '### Chaeyoung is a member of TWICE.',
            ));

        $draftTalentFactory = Mockery::mock(DraftTalentFactoryInterface::class);
        $draftTalentFactory->shouldReceive('create')
            ->twice()
            ->andReturn($translateTalentInfo->enTalent);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(DraftTalentFactoryInterface::class, $draftTalentFactory);

        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $talents = $translateTalent->process($input);

        $this->assertCount(2, $talents);
    }

    /**
     * 異常系：AGENCY_ACTORが自分の所属していないグループのメンバーを翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedAgencyScope(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $anotherAgencyId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $anotherAgencyId, [], []);

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
        $draftTalentFactory = Mockery::mock(DraftTalentFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(DraftTalentFactoryInterface::class, $draftTalentFactory);

        $this->setPolicyEvaluatorResult(false);

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
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedAgencyActor(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = (string) $translateTalentInfo->agencyIdentifier;
        $groupIds = array_map(static fn ($groupId) => (string) $groupId, $translateTalentInfo->groupIdentifiers);
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $agencyId, $groupIds, []);

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

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateTalent')
            ->twice()
            ->andReturn(new TranslatedTalentData(
                translatedName: 'Chaeyoung',
                translatedRealName: 'Son Chaeyoung',
                translatedCareer: '### Chaeyoung is a member of TWICE.',
            ));

        $draftTalentFactory = Mockery::mock(DraftTalentFactoryInterface::class);
        $draftTalentFactory->shouldReceive('create')
            ->twice()
            ->andReturn($translateTalentInfo->enTalent);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(DraftTalentFactoryInterface::class, $draftTalentFactory);

        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $talents = $translateTalent->process($input);

        $this->assertCount(2, $talents);
    }

    /**
     * 異常系：TALENT_ACTORが自分の所属していないグループのメンバーを翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedTalentScope(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = (string) $translateTalentInfo->agencyIdentifier;
        $anotherGroupId = StrTestHelper::generateUuid();
        $anotherTalentId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $agencyId, [$anotherGroupId], [$anotherTalentId]);

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
        $draftTalentFactory = Mockery::mock(DraftTalentFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(DraftTalentFactoryInterface::class, $draftTalentFactory);

        $this->setPolicyEvaluatorResult(false);

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
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedTalentActor(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = (string) $translateTalentInfo->agencyIdentifier;
        $groupIds = array_map(static fn ($groupId) => (string) $groupId, $translateTalentInfo->groupIdentifiers);
        $talentId = (string) $translateTalentInfo->talentIdentifier;
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $agencyId, $groupIds, [$talentId]);

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

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateTalent')
            ->twice()
            ->andReturn(new TranslatedTalentData(
                translatedName: 'Chaeyoung',
                translatedRealName: 'Son Chaeyoung',
                translatedCareer: '### Chaeyoung is a member of TWICE.',
            ));

        $draftTalentFactory = Mockery::mock(DraftTalentFactoryInterface::class);
        $draftTalentFactory->shouldReceive('create')
            ->twice()
            ->andReturn($translateTalentInfo->enTalent);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(DraftTalentFactoryInterface::class, $draftTalentFactory);

        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $talents = $translateTalent->process($input);

        $this->assertCount(2, $talents);
    }

    /**
     * 正常系：SENIOR_COLLABORATORがメンバーを翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

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

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateTalent')
            ->twice()
            ->andReturn(new TranslatedTalentData(
                translatedName: 'Chaeyoung',
                translatedRealName: 'Son Chaeyoung',
                translatedCareer: '### Chaeyoung is a member of TWICE.',
            ));

        $draftTalentFactory = Mockery::mock(DraftTalentFactoryInterface::class);
        $draftTalentFactory->shouldReceive('create')
            ->twice()
            ->andReturn($translateTalentInfo->enTalent);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(DraftTalentFactoryInterface::class, $draftTalentFactory);

        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $talents = $translateTalent->process($input);

        $this->assertCount(2, $talents);
    }

    /**
     * 異常系：NONEロールがメンバーを翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedNoneRole(): void
    {
        $translateTalentInfo = $this->createTranslateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

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
        $draftTalentFactory = Mockery::mock(DraftTalentFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(DraftTalentFactoryInterface::class, $draftTalentFactory);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(UnauthorizedException::class);
        $translateTalent = $this->app->make(TranslateTalentInterface::class);
        $translateTalent->process($input);
    }

    /**
     * @return TranslateTalentTestData
     */
    private function createTranslateTalentInfo(): TranslateTalentTestData
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
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
        $career = new Career('### 채영은 트와이스의 멤버입니다.');
        $status = ApprovalStatus::UnderReview;
        $version = new Version(1);
        $talent = new Talent(
            $talentIdentifier,
            $translationSetIdentifier,
            new Slug('chaeyoung'),
            $language,
            $name,
            $realName,
            $agencyIdentifier,
            $groupIdentifiers,
            $birthday,
            $career,
            $version,
        );

        $jaTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $japanese = Language::JAPANESE;
        $jaName = new TalentName('チェヨン');
        $jaRealName = new RealName('ソン・チェヨン');
        $jaCareer = new Career('### チェヨンはTWICEのメンバーです。');
        $jaTalent = new DraftTalent(
            $jaTalentIdentifier,
            $talentIdentifier,
            $translationSetIdentifier,
            new Slug('chaeyoung'),
            $editorIdentifier,
            $japanese,
            $jaName,
            $jaRealName,
            $agencyIdentifier,
            $groupIdentifiers,
            $birthday,
            $jaCareer,
            ApprovalStatus::Pending,
        );

        $enTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $english = Language::ENGLISH;
        $enName = new TalentName('Chaeyoung');
        $enRealName = new RealName('Son Chaeyoung');
        $enCareer = new Career('### Chaeyoung is a member of TWICE.');
        $enTalent = new DraftTalent(
            $enTalentIdentifier,
            $talentIdentifier,
            $translationSetIdentifier,
            new Slug('chaeyoung'),
            $editorIdentifier,
            $english,
            $enName,
            $enRealName,
            $agencyIdentifier,
            $groupIdentifiers,
            $birthday,
            $enCareer,
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
        public PrincipalIdentifier      $editorIdentifier,
        public Language                 $language,
        public TalentName               $name,
        public RealName                 $realName,
        public AgencyIdentifier         $agencyIdentifier,
        public array                    $groupIdentifiers,
        public Birthday                 $birthday,
        public Career                   $career,
        public ApprovalStatus           $status,
        public Talent                   $talent,
        public Language                 $english,
        public DraftTalent              $enTalent,
        public Language                 $japanese,
        public DraftTalent              $jaTalent,
    ) {
    }
}
