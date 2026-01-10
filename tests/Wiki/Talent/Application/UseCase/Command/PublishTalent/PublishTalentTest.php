<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\PublishTalent;

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
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Application\Exception\ExistsApprovedButNotTranslatedTalentException;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Application\UseCase\Command\PublishTalent\PublishTalent;
use Source\Wiki\Talent\Application\UseCase\Command\PublishTalent\PublishTalentInput;
use Source\Wiki\Talent\Application\UseCase\Command\PublishTalent\PublishTalentInterface;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Entity\TalentHistory;
use Source\Wiki\Talent\Domain\Entity\TalentSnapshot;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Factory\TalentFactoryInterface;
use Source\Wiki\Talent\Domain\Factory\TalentHistoryFactoryInterface;
use Source\Wiki\Talent\Domain\Factory\TalentSnapshotFactoryInterface;
use Source\Wiki\Talent\Domain\Repository\DraftTalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentHistoryRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentSnapshotRepositoryInterface;
use Source\Wiki\Talent\Domain\Service\TalentServiceInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentHistoryIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Source\Wiki\Talent\Domain\ValueObject\TalentSnapshotIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PublishTalentTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        // TODO: 各実装クラス作ったら削除する
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $talentService = Mockery::mock(TalentServiceInterface::class);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $talentFactory = Mockery::mock(TalentFactoryInterface::class);
        $this->app->instance(TalentFactoryInterface::class, $talentFactory);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);
        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $this->assertInstanceOf(PublishTalent::class, $publishTalent);
    }

    /**
     * 正常系：正しく変更されたTalentが公開されること（すでに一度公開されたことがある場合）.
     * スナップショットが保存されることも確認.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWhenAlreadyPublished(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $publishTalentInfo = $this->createPublishTalentInfo(
            operatorIdentifier: $principalIdentifier,
        );

        $input = new PublishTalentInput(
            $publishTalentInfo->talentIdentifier,
            $publishTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $exName = new TalentName('지효');
        $exRealName = new RealName('박지수');
        $exAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $exGroupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new GroupIdentifier(StrTestHelper::generateUuid()),
        ];
        $exBirthday = new Birthday(new DateTimeImmutable('1995-01-01'));
        $exCareer = new Career('### 트와이스 지효: 10년의 연습생 생활을 거쳐 K팝 정상에 선 리더
트와이스(TWICE)의 리더이자 메인보컬인 지효(본명: 박지효)는 파워풀한 가창력과 따뜻한 리더십으로 그룹을 이끌고 있는 핵심 멤버입니다. 10년이 넘는 긴 연습생 기간을 거쳐 데뷔한 것으로도 잘 알려져 있으며, 흔들림 없는 실력과 밝은 에너지로 전 세계 팬들의 사랑을 받고 있습니다.
1997년 2월 1일 경기도 구리에서 태어난 지효는 2005년 JYP 엔터테인먼트에 입사하여 10년 4개월이라는 긴 시간 동안 연습생으로 실력을 갈고닦았습니다. 오랜 기다림 끝에 2015년 Mnet 서바이벌 프로그램 \'식스틴(SIXTEEN)\'을 통해 최종 멤버로 발탁되었고, 그해 10월 트와이스로 정식 데뷔했습니다. 데뷔 후에는 멤버들의 투표를 통해 자연스럽게 리더 역할을 맡게 되었습니다.
팀 내에서 지효는 파워풀하고 안정적인 가창력을 자랑하는 메인보컬을 담당하고 있습니다. 풍부한 성량과 넓은 음역대를 바탕으로 트와이스 음악의 중심을 잡아주며, 격렬한 안무 중에도 흔들림 없는 라이브 실력을 선보여 \'믿고 듣는 지효\'라는 평을 받습니다.
2023년 8월에는 첫 솔로 미니 앨범 \'ZONE\'을 발매하며 성공적인 솔로 아티스트로서의 역량을 입증했습니다. 타이틀곡 \'Killin\' Me Good\'을 통해 자신만의 음악적 색깔과 매력을 선보이며 국내외 팬들로부터 뜨거운 반응을 얻었습니다.
지효는 무대 위 카리스마 넘치는 모습과 달리, 평소에는 멤버들을 살뜰히 챙기는 다정하고 털털한 성격으로 알려져 있습니다. 긍정적이고 건강한 이미지로 다양한 예능 프로그램에서도 활약하며 대중에게 친근하게 다가가고 있습니다. 오랜 시간 꿈을 향해 달려온 노력의 아이콘이자, 이제는 K팝을 대표하는 아티스트로 굳건히 자리매김한 지효의 앞으로의 활동에 더욱 기대가 모아지고 있습니다.');
        $exImagePath = new ImagePath('/resources/public/images/after.webp');
        $link4 = new ExternalContentLink('https://example4.youtube.com/watch?v=dQw4w9WgXcQ');
        $link5 = new ExternalContentLink('https://example5.youtube.com/watch?v=dQw4w9WgXcQ');
        $exRelevantVideoLinks = new RelevantVideoLinks([$link4, $link5]);
        $exVersion = new Version(1);
        $publishedTalent = new Talent(
            $publishTalentInfo->publishedTalentIdentifier,
            $publishTalentInfo->translationSetIdentifier,
            $publishTalentInfo->language,
            $exName,
            $exRealName,
            $exAgencyIdentifier,
            $exGroupIdentifiers,
            $exBirthday,
            $exCareer,
            $exImagePath,
            $exRelevantVideoLinks,
            $exVersion,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($publishTalentInfo->talentIdentifier)
            ->andReturn($publishTalentInfo->draftTalent);
        $draftTalentRepository->shouldReceive('delete')
            ->once()
            ->with($publishTalentInfo->draftTalent)
            ->andReturn(null);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($publishTalentInfo->publishedTalentIdentifier)
            ->andReturn($publishedTalent);
        $talentRepository->shouldReceive('save')
            ->once()
            ->with($publishedTalent)
            ->andReturn(null);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($publishTalentInfo->translationSetIdentifier, $publishTalentInfo->talentIdentifier)
            ->andReturn(false);

        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($publishTalentInfo->history);

        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryRepository->shouldReceive('save')
            ->once()
            ->with($publishTalentInfo->history)
            ->andReturn(null);

        // スナップショットのモック
        $snapshot = $publishTalentInfo->snapshot;
        $talentSnapshotFactory = Mockery::mock(TalentSnapshotFactoryInterface::class);
        $talentSnapshotFactory->shouldReceive('create')
            ->once()
            ->with($publishedTalent)
            ->andReturn($snapshot);

        $talentSnapshotRepository = Mockery::mock(TalentSnapshotRepositoryInterface::class);
        $talentSnapshotRepository->shouldReceive('save')
            ->once()
            ->with($snapshot)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);
        $this->app->instance(TalentSnapshotFactoryInterface::class, $talentSnapshotFactory);
        $this->app->instance(TalentSnapshotRepositoryInterface::class, $talentSnapshotRepository);
        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $publishedTalent = $publishTalent->process($input);
        $this->assertSame((string)$publishTalentInfo->publishedTalentIdentifier, (string)$publishedTalent->talentIdentifier());
        $this->assertSame($publishTalentInfo->language->value, $publishedTalent->language()->value);
        $this->assertSame((string)$publishTalentInfo->name, (string)$publishedTalent->name());
        $this->assertSame((string)$publishTalentInfo->realName, (string)$publishedTalent->realName());
        $this->assertSame($publishTalentInfo->groupIdentifiers, $publishedTalent->groupIdentifiers());
        $this->assertSame($publishTalentInfo->birthday, $publishedTalent->birthday());
        $this->assertSame((string)$publishTalentInfo->career, (string)$publishedTalent->career());
        $this->assertSame((string)$publishTalentInfo->imagePath, (string)$publishedTalent->imageLink());
        $this->assertSame($publishTalentInfo->relevantVideoLinks->toStringArray(), $publishedTalent->relevantVideoLinks()->toStringArray());
        $this->assertSame($exVersion->value() + 1, $publishedTalent->version()->value());
    }

    /**
     * 正常系：正しく変更されたTalentが公開されること（初めて公開する場合）.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessForTheFirstTime(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $publishTalentInfo = $this->createPublishTalentInfo(
            hasPublishedTalent: false,
            operatorIdentifier: $principalIdentifier,
        );

        $talent = new DraftTalent(
            $publishTalentInfo->talentIdentifier,
            null,
            $publishTalentInfo->translationSetIdentifier,
            $publishTalentInfo->editorIdentifier,
            $publishTalentInfo->language,
            $publishTalentInfo->name,
            $publishTalentInfo->realName,
            $publishTalentInfo->agencyIdentifier,
            $publishTalentInfo->groupIdentifiers,
            $publishTalentInfo->birthday,
            $publishTalentInfo->career,
            $publishTalentInfo->imagePath,
            $publishTalentInfo->relevantVideoLinks,
            $publishTalentInfo->status,
        );

        $input = new PublishTalentInput(
            $publishTalentInfo->talentIdentifier,
            $publishTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $version = new Version(1);
        $createdTalent = new Talent(
            $publishTalentInfo->publishedTalentIdentifier,
            $publishTalentInfo->translationSetIdentifier,
            $publishTalentInfo->language,
            $publishTalentInfo->name,
            new RealName(''),
            null,
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
            $version,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($publishTalentInfo->talentIdentifier)
            ->andReturn($talent);
        $draftTalentRepository->shouldReceive('delete')
            ->once()
            ->with($talent)
            ->andReturn(null);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('save')
            ->once()
            ->with($createdTalent)
            ->andReturn(null);

        $talentFactory = Mockery::mock(TalentFactoryInterface::class);
        $talentFactory->shouldReceive('create')
            ->once()
            ->with($publishTalentInfo->translationSetIdentifier, $publishTalentInfo->language, $publishTalentInfo->name)
            ->andReturn($createdTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($publishTalentInfo->translationSetIdentifier, $publishTalentInfo->talentIdentifier)
            ->andReturn(false);

        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($publishTalentInfo->history);

        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryRepository->shouldReceive('save')
            ->once()
            ->with($publishTalentInfo->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentFactoryInterface::class, $talentFactory);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);
        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $publishedTalent = $publishTalent->process($input);
        $this->assertSame((string)$publishTalentInfo->publishedTalentIdentifier, (string)$publishedTalent->talentIdentifier());
        $this->assertSame($publishTalentInfo->language->value, $publishedTalent->language()->value);
        $this->assertSame((string)$publishTalentInfo->name, (string)$publishedTalent->name());
        $this->assertSame((string)$publishTalentInfo->realName, (string)$publishedTalent->realName());
        $this->assertSame($publishTalentInfo->groupIdentifiers, $publishedTalent->groupIdentifiers());
        $this->assertSame($publishTalentInfo->birthday, $publishedTalent->birthday());
        $this->assertSame((string)$publishTalentInfo->career, (string)$publishedTalent->career());
        $this->assertSame((string)$publishTalentInfo->imagePath, (string)$publishedTalent->imageLink());
        $this->assertSame($publishTalentInfo->relevantVideoLinks->toStringArray(), $publishedTalent->relevantVideoLinks()->toStringArray());
        $this->assertSame($version->value(), $publishedTalent->version()->value());
    }

    /**
     * 異常系：指定したIDに紐づくTalentが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundSong(): void
    {
        $publishTalentInfo = $this->createPublishTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new PublishTalentInput(
            $publishTalentInfo->talentIdentifier,
            $publishTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($publishTalentInfo->talentIdentifier)
            ->andReturn(null);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->expectException(TalentNotFoundException::class);
        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $publishTalent->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $publishTalentInfo = $this->createPublishTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new PublishTalentInput(
            $publishTalentInfo->talentIdentifier,
            $publishTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($publishTalentInfo->talentIdentifier)
            ->andReturn($publishTalentInfo->draftTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->expectException(PrincipalNotFoundException::class);
        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $publishTalent->process($input);
    }

    /**
     * 異常系：承認ステータスがUnderReview以外の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testInvalidStatus(): void
    {
        $publishTalentInfo = $this->createPublishTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new PublishTalentInput(
            $publishTalentInfo->talentIdentifier,
            $publishTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $status = ApprovalStatus::Approved;
        $talent = new DraftTalent(
            $publishTalentInfo->talentIdentifier,
            $publishTalentInfo->publishedTalentIdentifier,
            $publishTalentInfo->translationSetIdentifier,
            $publishTalentInfo->editorIdentifier,
            $publishTalentInfo->language,
            $publishTalentInfo->name,
            $publishTalentInfo->realName,
            $publishTalentInfo->agencyIdentifier,
            $publishTalentInfo->groupIdentifiers,
            $publishTalentInfo->birthday,
            $publishTalentInfo->career,
            $publishTalentInfo->imagePath,
            $publishTalentInfo->relevantVideoLinks,
            $status,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($publishTalentInfo->talentIdentifier)
            ->andReturn($talent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->expectException(InvalidStatusException::class);
        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $publishTalent->process($input);
    }

    /**
     * 異常系：承認済みだが、翻訳が反映されていない承認済みのメンバーがいる場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testHasApprovedButNotTranslatedTalent(): void
    {
        $publishTalentInfo = $this->createPublishTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new PublishTalentInput(
            $publishTalentInfo->talentIdentifier,
            $publishTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($publishTalentInfo->talentIdentifier)
            ->andReturn($publishTalentInfo->draftTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($publishTalentInfo->translationSetIdentifier, $publishTalentInfo->talentIdentifier)
            ->andReturn(true);

        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->expectException(ExistsApprovedButNotTranslatedTalentException::class);
        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $publishTalent->process($input);
    }

    /**
     * 異常系：公開されているメンバー情報が取得できない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundPublishedAgency(): void
    {
        $publishTalentInfo = $this->createPublishTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new PublishTalentInput(
            $publishTalentInfo->talentIdentifier,
            $publishTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($publishTalentInfo->talentIdentifier)
            ->andReturn($publishTalentInfo->draftTalent);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($publishTalentInfo->publishedTalentIdentifier)
            ->andReturn(null);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($publishTalentInfo->translationSetIdentifier, $publishTalentInfo->talentIdentifier)
            ->andReturn(false);

        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->expectException(TalentNotFoundException::class);
        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $publishTalent->process($input);
    }

    /**
     * 異常系：承認権限がないロール（Collaborator）の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedRole(): void
    {
        $publishTalentInfo = $this->createPublishTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new PublishTalentInput(
            $publishTalentInfo->talentIdentifier,
            $publishTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($publishTalentInfo->talentIdentifier)
            ->andReturn($publishTalentInfo->draftTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(UnauthorizedException::class);
        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $publishTalent->process($input);
    }

    /**
     * 異常系：AGENCY_ACTORが自分の所属していないグループのメンバーを公開しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedAgencyScope(): void
    {
        $publishTalentInfo = $this->createPublishTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $anotherAgencyId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $anotherAgencyId, [], []);

        $input = new PublishTalentInput(
            $publishTalentInfo->talentIdentifier,
            $publishTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($publishTalentInfo->talentIdentifier)
            ->andReturn($publishTalentInfo->draftTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentFactory = Mockery::mock(TalentFactoryInterface::class);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentFactoryInterface::class, $talentFactory);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(UnauthorizedException::class);
        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $publishTalent->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORが自分の所属するグループのメンバーを公開できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedAgencyActor(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $publishTalentInfo = $this->createPublishTalentInfo(
            hasPublishedTalent: false,
            operatorIdentifier: $principalIdentifier,
        );

        $talent = new DraftTalent(
            $publishTalentInfo->talentIdentifier,
            null,
            $publishTalentInfo->translationSetIdentifier,
            $publishTalentInfo->editorIdentifier,
            $publishTalentInfo->language,
            $publishTalentInfo->name,
            $publishTalentInfo->realName,
            $publishTalentInfo->agencyIdentifier,
            $publishTalentInfo->groupIdentifiers,
            $publishTalentInfo->birthday,
            $publishTalentInfo->career,
            $publishTalentInfo->imagePath,
            $publishTalentInfo->relevantVideoLinks,
            $publishTalentInfo->status,
        );

        $agencyId = (string) $publishTalentInfo->agencyIdentifier;
        $groupIds = array_map(static fn ($groupId) => (string)$groupId, $publishTalentInfo->groupIdentifiers);
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $agencyId, $groupIds, []);

        $input = new PublishTalentInput(
            $publishTalentInfo->talentIdentifier,
            $publishTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $version = new Version(1);
        $createdTalent = new Talent(
            $publishTalentInfo->publishedTalentIdentifier,
            $publishTalentInfo->translationSetIdentifier,
            $publishTalentInfo->language,
            $publishTalentInfo->name,
            new RealName(''),
            null,
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
            $version,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($publishTalentInfo->talentIdentifier)
            ->andReturn($talent);
        $draftTalentRepository->shouldReceive('delete')
            ->once()
            ->with($talent)
            ->andReturn(null);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('save')
            ->once()
            ->with($createdTalent)
            ->andReturn(null);

        $talentFactory = Mockery::mock(TalentFactoryInterface::class);
        $talentFactory->shouldReceive('create')
            ->once()
            ->with($publishTalentInfo->translationSetIdentifier, $publishTalentInfo->language, $publishTalentInfo->name)
            ->andReturn($createdTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($publishTalentInfo->translationSetIdentifier, $publishTalentInfo->talentIdentifier)
            ->andReturn(false);

        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($publishTalentInfo->history);

        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryRepository->shouldReceive('save')
            ->once()
            ->with($publishTalentInfo->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentFactoryInterface::class, $talentFactory);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $publishTalent->process($input);
    }

    /**
     * 異常系：TALENT_ACTORが自分の所属していないグループのメンバーを公開しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedTalentScope(): void
    {
        $publishTalentInfo = $this->createPublishTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = (string) $publishTalentInfo->agencyIdentifier;
        $anotherGroupId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $agencyId, [$anotherGroupId], [$talentId]);

        $input = new PublishTalentInput(
            $publishTalentInfo->talentIdentifier,
            $publishTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($publishTalentInfo->talentIdentifier)
            ->andReturn($publishTalentInfo->draftTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(UnauthorizedException::class);
        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $publishTalent->process($input);
    }

    /**
     * 正常系：TALENT_ACTORが自分の所属するグループのメンバーを公開できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedTalentActor(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $publishTalentInfo = $this->createPublishTalentInfo(
            hasPublishedTalent: false,
            operatorIdentifier: $principalIdentifier,
        );

        $talent = new DraftTalent(
            $publishTalentInfo->talentIdentifier,
            null,
            $publishTalentInfo->translationSetIdentifier,
            $publishTalentInfo->editorIdentifier,
            $publishTalentInfo->language,
            $publishTalentInfo->name,
            $publishTalentInfo->realName,
            $publishTalentInfo->agencyIdentifier,
            $publishTalentInfo->groupIdentifiers,
            $publishTalentInfo->birthday,
            $publishTalentInfo->career,
            $publishTalentInfo->imagePath,
            $publishTalentInfo->relevantVideoLinks,
            $publishTalentInfo->status,
        );

        $agencyId = (string) $publishTalentInfo->agencyIdentifier;
        $groupIds = array_map(static fn ($groupId) => (string)$groupId, $publishTalentInfo->groupIdentifiers);
        $talentId = (string) $publishTalentInfo->talentIdentifier; // 自分自身のTalent IDを使用
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $agencyId, $groupIds, [$talentId]);

        $input = new PublishTalentInput(
            $publishTalentInfo->talentIdentifier,
            $publishTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $version = new Version(1);
        $createdTalent = new Talent(
            $publishTalentInfo->publishedTalentIdentifier,
            $publishTalentInfo->translationSetIdentifier,
            $publishTalentInfo->language,
            $publishTalentInfo->name,
            new RealName(''),
            null,
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
            $version,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($publishTalentInfo->talentIdentifier)
            ->andReturn($talent);
        $draftTalentRepository->shouldReceive('delete')
            ->once()
            ->with($talent)
            ->andReturn(null);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('save')
            ->once()
            ->with($createdTalent)
            ->andReturn(null);

        $talentFactory = Mockery::mock(TalentFactoryInterface::class);
        $talentFactory->shouldReceive('create')
            ->once()
            ->with($publishTalentInfo->translationSetIdentifier, $publishTalentInfo->language, $publishTalentInfo->name)
            ->andReturn($createdTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($publishTalentInfo->translationSetIdentifier, $publishTalentInfo->talentIdentifier)
            ->andReturn(false);

        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($publishTalentInfo->history);

        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryRepository->shouldReceive('save')
            ->once()
            ->with($publishTalentInfo->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentFactoryInterface::class, $talentFactory);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $publishTalent->process($input);
    }

    /**
     * 正常系：SENIOR_COLLABORATORがメンバーを公開できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $publishTalentInfo = $this->createPublishTalentInfo(
            hasPublishedTalent: false,
            operatorIdentifier: $principalIdentifier,
        );

        $talent = new DraftTalent(
            $publishTalentInfo->talentIdentifier,
            null,
            $publishTalentInfo->translationSetIdentifier,
            $publishTalentInfo->editorIdentifier,
            $publishTalentInfo->language,
            $publishTalentInfo->name,
            $publishTalentInfo->realName,
            $publishTalentInfo->agencyIdentifier,
            $publishTalentInfo->groupIdentifiers,
            $publishTalentInfo->birthday,
            $publishTalentInfo->career,
            $publishTalentInfo->imagePath,
            $publishTalentInfo->relevantVideoLinks,
            $publishTalentInfo->status,
        );

        $input = new PublishTalentInput(
            $publishTalentInfo->talentIdentifier,
            $publishTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $version = new Version(1);
        $createdTalent = new Talent(
            $publishTalentInfo->publishedTalentIdentifier,
            $publishTalentInfo->translationSetIdentifier,
            $publishTalentInfo->language,
            $publishTalentInfo->name,
            new RealName(''),
            null,
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
            $version,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($publishTalentInfo->talentIdentifier)
            ->andReturn($talent);
        $draftTalentRepository->shouldReceive('delete')
            ->once()
            ->with($talent)
            ->andReturn(null);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('save')
            ->once()
            ->with($createdTalent)
            ->andReturn(null);

        $talentFactory = Mockery::mock(TalentFactoryInterface::class);
        $talentFactory->shouldReceive('create')
            ->once()
            ->with($publishTalentInfo->translationSetIdentifier, $publishTalentInfo->language, $publishTalentInfo->name)
            ->andReturn($createdTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($publishTalentInfo->translationSetIdentifier, $publishTalentInfo->talentIdentifier)
            ->andReturn(false);

        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($publishTalentInfo->history);

        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryRepository->shouldReceive('save')
            ->once()
            ->with($publishTalentInfo->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentFactoryInterface::class, $talentFactory);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $publishTalent->process($input);
    }

    /**
     * 異常系：NONEロールがメンバーを公開しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedNoneRole(): void
    {
        $publishTalentInfo = $this->createPublishTalentInfo();

        $talent = new DraftTalent(
            $publishTalentInfo->talentIdentifier,
            null,
            $publishTalentInfo->translationSetIdentifier,
            $publishTalentInfo->editorIdentifier,
            $publishTalentInfo->language,
            $publishTalentInfo->name,
            $publishTalentInfo->realName,
            $publishTalentInfo->agencyIdentifier,
            $publishTalentInfo->groupIdentifiers,
            $publishTalentInfo->birthday,
            $publishTalentInfo->career,
            $publishTalentInfo->imagePath,
            $publishTalentInfo->relevantVideoLinks,
            $publishTalentInfo->status,
        );

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new PublishTalentInput(
            $publishTalentInfo->talentIdentifier,
            $publishTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($publishTalentInfo->talentIdentifier)
            ->andReturn($talent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(UnauthorizedException::class);
        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $publishTalent->process($input);
    }

    /**
     * @return PublishTalentTestData
     * @throws ExceedMaxRelevantVideoLinksException
     */
    private function createPublishTalentInfo(
        bool $hasPublishedTalent = true,
        ?PrincipalIdentifier $operatorIdentifier = null,
    ): PublishTalentTestData {
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new GroupIdentifier(StrTestHelper::generateUuid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $base64EncodedImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $externalContentLinks = [$link1, $link2, $link3];
        $relevantVideoLinks = new RelevantVideoLinks($externalContentLinks);

        $imageLink = new ImagePath('/resources/public/images/before.webp');

        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $status = ApprovalStatus::UnderReview;
        $talent = new DraftTalent(
            $talentIdentifier,
            $hasPublishedTalent ? $publishedTalentIdentifier : null,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $realName,
            $agencyIdentifier,
            $groupIdentifiers,
            $birthday,
            $career,
            $imageLink,
            $relevantVideoLinks,
            $status,
        );

        $historyIdentifier = new TalentHistoryIdentifier(StrTestHelper::generateUuid());
        $history = new TalentHistory(
            $historyIdentifier,
            HistoryActionType::Publish,
            $operatorIdentifier ?? new PrincipalIdentifier(StrTestHelper::generateUuid()),
            $talent->editorIdentifier(),
            $hasPublishedTalent ? $publishedTalentIdentifier : null,
            $talent->talentIdentifier(),
            $talent->status(),
            null,
            null,
            null,
            $talent->name(),
            new \DateTimeImmutable(),
        );

        // スナップショット（公開済みTalentからスナップショットを作成）
        $snapshot = new TalentSnapshot(
            new TalentSnapshotIdentifier(StrTestHelper::generateUuid()),
            $publishedTalentIdentifier,
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
            new Version(1),
            new \DateTimeImmutable(),
        );

        return new PublishTalentTestData(
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $realName,
            $agencyIdentifier,
            $groupIdentifiers,
            $birthday,
            $career,
            $base64EncodedImage,
            $link1,
            $link2,
            $link3,
            $relevantVideoLinks,
            $imageLink,
            $talentIdentifier,
            $status,
            $talent,
            $historyIdentifier,
            $history,
            $snapshot,
        );
    }
}


/**
 * テストデータを保持するクラス
 */
readonly class PublishTalentTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     * @param GroupIdentifier[] $groupIdentifiers
     */
    public function __construct(
        public TalentIdentifier         $publishedTalentIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public PrincipalIdentifier      $editorIdentifier,
        public Language                 $language,
        public TalentName               $name,
        public RealName                 $realName,
        public AgencyIdentifier         $agencyIdentifier,
        public array                    $groupIdentifiers,
        public Birthday                 $birthday,
        public Career                   $career,
        public string                   $base64EncodedImage,
        public ExternalContentLink      $link1,
        public ExternalContentLink      $link2,
        public ExternalContentLink      $link3,
        public RelevantVideoLinks       $relevantVideoLinks,
        public ImagePath                $imagePath,
        public TalentIdentifier         $talentIdentifier,
        public ApprovalStatus           $status,
        public DraftTalent              $draftTalent,
        public TalentHistoryIdentifier  $historyIdentifier,
        public TalentHistory            $history,
        public TalentSnapshot           $snapshot,
    ) {
    }
}
