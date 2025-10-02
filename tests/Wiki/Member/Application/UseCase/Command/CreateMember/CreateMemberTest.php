<?php

declare(strict_types=1);

namespace Tests\Wiki\Member\Application\UseCase\Command\CreateMember;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Member\Application\UseCase\Command\CreateMember\CreateMember;
use Source\Wiki\Member\Application\UseCase\Command\CreateMember\CreateMemberInput;
use Source\Wiki\Member\Application\UseCase\Command\CreateMember\CreateMemberInterface;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Member\Domain\Entity\Member;
use Source\Wiki\Member\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Member\Domain\Factory\DraftMemberFactoryInterface;
use Source\Wiki\Member\Domain\Repository\MemberRepositoryInterface;
use Source\Wiki\Member\Domain\ValueObject\Birthday;
use Source\Wiki\Member\Domain\ValueObject\Career;
use Source\Wiki\Member\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Source\Wiki\Member\Domain\ValueObject\MemberName;
use Source\Wiki\Member\Domain\ValueObject\RealName;
use Source\Wiki\Member\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateMemberTest extends TestCase
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
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $memberRepository = Mockery::mock(MemberRepositoryInterface::class);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(MemberRepositoryInterface::class, $memberRepository);
        $createMember = $this->app->make(CreateMemberInterface::class);
        $this->assertInstanceOf(CreateMember::class, $createMember);
    }

    /**
     * 正常系：正しくMember Entityが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testProcess(): void
    {
        $publishedMemberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new MemberName('채영');
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
        $base64EncodedImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $externalContentLinks = [$link1, $link2, $link3];
        $relevantVideoLinks = new RelevantVideoLinks($externalContentLinks);
        $input = new CreateMemberInput(
            $publishedMemberIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $base64EncodedImage,
            $relevantVideoLinks,
        );

        $imageLink = new ImagePath('/resources/public/images/before.webp');
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($base64EncodedImage)
            ->andReturn($imageLink);

        $memberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $status = ApprovalStatus::Pending;
        $member = new DraftMember(
            $memberIdentifier,
            $publishedMemberIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $editorIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imageLink,
            $relevantVideoLinks,
            $status,
        );

        $publishedMember = new Member(
            $publishedMemberIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imageLink,
            $relevantVideoLinks,
        );

        $memberFactory = Mockery::mock(DraftMemberFactoryInterface::class);
        $memberFactory->shouldReceive('create')
            ->once()
            ->with($editorIdentifier, $translation, $name)
            ->andReturn($member);

        $memberRepository = Mockery::mock(MemberRepositoryInterface::class);
        $memberRepository->shouldReceive('findById')
            ->once()
            ->with($publishedMemberIdentifier)
            ->andReturn($publishedMember);
        $memberRepository->shouldReceive('saveDraft')
            ->once()
            ->with($member)
            ->andReturn(null);

        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftMemberFactoryInterface::class, $memberFactory);
        $this->app->instance(MemberRepositoryInterface::class, $memberRepository);
        $createMember = $this->app->make(CreateMemberInterface::class);
        $member = $createMember->process($input);
        $this->assertTrue(UlidValidator::isValid((string)$member->memberIdentifier()));
        $this->assertSame((string)$publishedMemberIdentifier, (string)$member->publishedMemberIdentifier());
        $this->assertSame((string)$editorIdentifier, (string)$member->editorIdentifier());
        $this->assertSame($translation->value, $member->translation()->value);
        $this->assertSame((string)$name, (string)$member->name());
        $this->assertSame((string)$realName, (string)$member->realName());
        $this->assertSame($groupIdentifiers, $member->groupIdentifiers());
        $this->assertSame($birthday, $member->birthday());
        $this->assertSame((string)$career, (string)$member->career());
        $this->assertSame((string)$imageLink, (string)$member->imageLink());
        $this->assertSame($relevantVideoLinks->toStringArray(), $member->relevantVideoLinks()->toStringArray());
        $this->assertSame($status, $member->status());
    }
}
