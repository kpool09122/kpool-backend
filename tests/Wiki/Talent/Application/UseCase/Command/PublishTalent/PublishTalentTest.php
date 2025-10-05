<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\PublishTalent;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Source\Wiki\Talent\Application\Exception\ExistsApprovedButNotTranslatedTalentException;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Application\UseCase\Command\PublishTalent\PublishTalent;
use Source\Wiki\Talent\Application\UseCase\Command\PublishTalent\PublishTalentInput;
use Source\Wiki\Talent\Application\UseCase\Command\PublishTalent\PublishTalentInterface;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Factory\TalentFactoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Service\TalentServiceInterface;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
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
        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $talentService = Mockery::mock(TalentServiceInterface::class);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $talentFactory = Mockery::mock(TalentFactoryInterface::class);
        $this->app->instance(TalentFactoryInterface::class, $talentFactory);
        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $this->assertInstanceOf(PublishTalent::class, $publishTalent);
    }

    /**
     * 正常系：正しく変更されたTalentが公開されること（すでに一度公開されたことがある場合）.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     */
    public function testProcessWhenAlreadyPublished(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $imagePath = new ImagePath('/resources/public/images/before.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $externalContentLinks = [$link1, $link2, $link3];
        $relevantVideoLinks = new RelevantVideoLinks($externalContentLinks);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new PublishTalentInput(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );

        $exName = new TalentName('지효');
        $exRealName = new RealName('박지수');
        $exGroupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
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
        $publishedTalent = new Talent(
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $translation,
            $exName,
            $exRealName,
            $exGroupIdentifiers,
            $exBirthday,
            $exCareer,
            $exImagePath,
            $exRelevantVideoLinks,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn($talent);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($publishedTalentIdentifier)
            ->andReturn($publishedTalent);
        $talentRepository->shouldReceive('save')
            ->once()
            ->with($publishedTalent)
            ->andReturn(null);
        $talentRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($talent)
            ->andReturn(null);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($translationSetIdentifier, $talentIdentifier)
            ->andReturn(false);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $publishedTalent = $publishTalent->process($input);
        $this->assertSame((string)$publishedTalentIdentifier, (string)$publishedTalent->talentIdentifier());
        $this->assertSame($translation->value, $publishedTalent->translation()->value);
        $this->assertSame((string)$name, (string)$publishedTalent->name());
        $this->assertSame((string)$realName, (string)$publishedTalent->realName());
        $this->assertSame($groupIdentifiers, $publishedTalent->groupIdentifiers());
        $this->assertSame($birthday, $publishedTalent->birthday());
        $this->assertSame((string)$career, (string)$publishedTalent->career());
        $this->assertSame((string)$imagePath, (string)$publishedTalent->imageLink());
        $this->assertSame($relevantVideoLinks->toStringArray(), $publishedTalent->relevantVideoLinks()->toStringArray());
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
     */
    public function testProcessForTheFirstTime(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $imagePath = new ImagePath('/resources/public/images/before.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $externalContentLinks = [$link1, $link2, $link3];
        $relevantVideoLinks = new RelevantVideoLinks($externalContentLinks);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new PublishTalentInput(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $talent = new DraftTalent(
            $talentIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );

        $createdTalent = new Talent(
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            new RealName(''),
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn($talent);
        $talentRepository->shouldReceive('save')
            ->once()
            ->with($createdTalent)
            ->andReturn(null);
        $talentRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($talent)
            ->andReturn(null);

        $talentFactory = Mockery::mock(TalentFactoryInterface::class);
        $talentFactory->shouldReceive('create')
            ->once()
            ->with($translationSetIdentifier, $translation, $name)
            ->andReturn($createdTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($translationSetIdentifier, $talentIdentifier)
            ->andReturn(false);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentFactoryInterface::class, $talentFactory);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $publishedTalent = $publishTalent->process($input);
        $this->assertSame((string)$publishedTalentIdentifier, (string)$publishedTalent->talentIdentifier());
        $this->assertSame($translation->value, $publishedTalent->translation()->value);
        $this->assertSame((string)$name, (string)$publishedTalent->name());
        $this->assertSame((string)$realName, (string)$publishedTalent->realName());
        $this->assertSame($groupIdentifiers, $publishedTalent->groupIdentifiers());
        $this->assertSame($birthday, $publishedTalent->birthday());
        $this->assertSame((string)$career, (string)$publishedTalent->career());
        $this->assertSame((string)$imagePath, (string)$publishedTalent->imageLink());
        $this->assertSame($relevantVideoLinks->toStringArray(), $publishedTalent->relevantVideoLinks()->toStringArray());
    }

    /**
     * 異常系：指定したIDに紐づくTalentが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testWhenNotFoundSong(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new PublishTalentInput(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn(null);

        $talentService = Mockery::mock(TalentServiceInterface::class);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);

        $this->expectException(TalentNotFoundException::class);
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
     */
    public function testInvalidStatus(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $imagePath = new ImagePath('/resources/public/images/before.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $externalContentLinks = [$link1, $link2, $link3];
        $relevantVideoLinks = new RelevantVideoLinks($externalContentLinks);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new PublishTalentInput(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $principal,
        );

        $status = ApprovalStatus::Approved;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn($talent);

        $talentService = Mockery::mock(TalentServiceInterface::class);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);

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
     */
    public function testHasApprovedButNotTranslatedTalent(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $imagePath = new ImagePath('/resources/public/images/before.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $externalContentLinks = [$link1, $link2, $link3];
        $relevantVideoLinks = new RelevantVideoLinks($externalContentLinks);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new PublishTalentInput(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn($talent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($translationSetIdentifier, $talentIdentifier)
            ->andReturn(true);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);

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
     */
    public function testWhenNotFoundPublishedAgency(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $imagePath = new ImagePath('/resources/public/images/before.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $externalContentLinks = [$link1, $link2, $link3];
        $relevantVideoLinks = new RelevantVideoLinks($externalContentLinks);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new PublishTalentInput(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn($talent);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($publishedTalentIdentifier)
            ->andReturn(null);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($translationSetIdentifier, $talentIdentifier)
            ->andReturn(false);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);

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
     */
    public function testUnauthorizedRole(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('経歴');
        $imagePath = new ImagePath('/resources/public/images/before.webp');
        $relevantVideoLinks = new RelevantVideoLinks([]);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::COLLABORATOR, null, [], null);

        $input = new PublishTalentInput(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn($talent);

        $talentService = Mockery::mock(TalentServiceInterface::class);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);

        $this->expectException(UnauthorizedException::class);
        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $publishTalent->process($input);
    }

    /**
     * 異常系：GROUP_ACTORが自分の所属していないグループのメンバーを公開しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedGroupScope(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupId1 = StrTestHelper::generateUlid();
        $groupId2 = StrTestHelper::generateUlid();
        $groupIdentifiers = [
            new GroupIdentifier($groupId1),
            new GroupIdentifier($groupId2),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('経歴');
        $imagePath = new ImagePath('/resources/public/images/before.webp');
        $relevantVideoLinks = new RelevantVideoLinks([]);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherGroupId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, null, [$anotherGroupId], null);

        $input = new PublishTalentInput(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn($talent);

        $talentService = Mockery::mock(TalentServiceInterface::class);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);

        $this->expectException(UnauthorizedException::class);
        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $publishTalent->process($input);
    }

    /**
     * 正常系：GROUP_ACTORが自分の所属するグループのメンバーを公開できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     */
    public function testAuthorizedGroupActor(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupId1 = StrTestHelper::generateUlid();
        $groupId2 = StrTestHelper::generateUlid();
        $groupIdentifiers = [
            new GroupIdentifier($groupId1),
            new GroupIdentifier($groupId2),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('経歴');
        $imagePath = new ImagePath('/resources/public/images/before.webp');
        $relevantVideoLinks = new RelevantVideoLinks([]);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, null, [$groupId1], null);

        $input = new PublishTalentInput(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $talent = new DraftTalent(
            $talentIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );

        $createdTalent = new Talent(
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            new RealName(''),
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn($talent);
        $talentRepository->shouldReceive('save')
            ->once()
            ->with($createdTalent)
            ->andReturn(null);
        $talentRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($talent)
            ->andReturn(null);

        $talentFactory = Mockery::mock(TalentFactoryInterface::class);
        $talentFactory->shouldReceive('create')
            ->once()
            ->with($translationSetIdentifier, $translation, $name)
            ->andReturn($createdTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($translationSetIdentifier, $talentIdentifier)
            ->andReturn(false);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentFactoryInterface::class, $talentFactory);
        $this->app->instance(TalentServiceInterface::class, $talentService);

        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $result = $publishTalent->process($input);

        $this->assertInstanceOf(Talent::class, $result);
    }

    /**
     * 異常系：MEMBER_ACTORが自分の所属していないグループのメンバーを公開しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedTalentScope(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupId1 = StrTestHelper::generateUlid();
        $groupId2 = StrTestHelper::generateUlid();
        $groupIdentifiers = [
            new GroupIdentifier($groupId1),
            new GroupIdentifier($groupId2),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('経歴');
        $imagePath = new ImagePath('/resources/public/images/before.webp');
        $relevantVideoLinks = new RelevantVideoLinks([]);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherGroupId = StrTestHelper::generateUlid();
        $talentId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, null, [$anotherGroupId], $talentId);

        $input = new PublishTalentInput(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn($talent);

        $talentService = Mockery::mock(TalentServiceInterface::class);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);

        $this->expectException(UnauthorizedException::class);
        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $publishTalent->process($input);
    }

    /**
     * 正常系：MEMBER_ACTORが自分の所属するグループのメンバーを公開できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     */
    public function testAuthorizedTalentActor(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupId1 = StrTestHelper::generateUlid();
        $groupId2 = StrTestHelper::generateUlid();
        $groupIdentifiers = [
            new GroupIdentifier($groupId1),
            new GroupIdentifier($groupId2),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('経歴');
        $imagePath = new ImagePath('/resources/public/images/before.webp');
        $relevantVideoLinks = new RelevantVideoLinks([]);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $talentId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, null, [$groupId1], $talentId);

        $input = new PublishTalentInput(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $principal,
        );

        $status = ApprovalStatus::UnderReview;
        $talent = new DraftTalent(
            $talentIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );

        $createdTalent = new Talent(
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            new RealName(''),
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn($talent);
        $talentRepository->shouldReceive('save')
            ->once()
            ->with($createdTalent)
            ->andReturn(null);
        $talentRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($talent)
            ->andReturn(null);

        $talentFactory = Mockery::mock(TalentFactoryInterface::class);
        $talentFactory->shouldReceive('create')
            ->once()
            ->with($translationSetIdentifier, $translation, $name)
            ->andReturn($createdTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($translationSetIdentifier, $talentIdentifier)
            ->andReturn(false);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentFactoryInterface::class, $talentFactory);
        $this->app->instance(TalentServiceInterface::class, $talentService);

        $publishTalent = $this->app->make(PublishTalentInterface::class);
        $result = $publishTalent->process($input);

        $this->assertInstanceOf(Talent::class, $result);
    }
}
