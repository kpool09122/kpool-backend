<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\PublishAgency;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\Exception\ExistsApprovedButNotTranslatedAgencyException;
use Source\Wiki\Agency\Application\Service\AgencyServiceInterface;
use Source\Wiki\Agency\Application\UseCase\Command\PublishAgency\PublishAgency;
use Source\Wiki\Agency\Application\UseCase\Command\PublishAgency\PublishAgencyInput;
use Source\Wiki\Agency\Application\UseCase\Command\PublishAgency\PublishAgencyInterface;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Factory\AgencyFactoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PublishAgencyTest extends TestCase
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
        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $agencyFactory = Mockery::mock(AgencyFactoryInterface::class);
        $this->app->instance(AgencyFactoryInterface::class, $agencyFactory);
        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $this->assertInstanceOf(PublishAgency::class, $publishAgency);
    }

    /**
     * 正常系：正しく変更されたAgencyが公開されること（すでに一度公開されたことがある場合）.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     */
    public function testProcessWhenAlreadyPublished(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $CEO = new CEO('J.Y. Park');
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description('### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다. HYBE, SM, YG엔터테인먼트와 함께 한국 연예계를 이끄는 **\'BIG4\'** 중 하나로 꼽힙니다.
**\'진실, 성실, 겸손\'**이라는 가치관을 매우 중시하며, 소속 아티스트의 노래나 댄스 실력뿐만 아니라 인성을 존중하는 육성 방침으로 알려져 있습니다. 이러한 철학은 박진영이 오디션 프로그램 등에서 보여주는 모습을 통해서도 널리 알려져 있습니다.
음악적인 면에서는 설립자인 박진영이 직접 프로듀서로서 많은 곡 작업에 참여하여, 대중에게 사랑받는 캐치한 히트곡을 수많이 만들어왔습니다.
---
### 주요 소속 아티스트
지금까지 **원더걸스(Wonder Girls)**, **2PM**, **미쓰에이(Miss A)**와 같이 K팝의 역사를 만들어 온 그룹들을 배출해왔습니다.
현재도
* **트와이스 (TWICE)**
* **스트레이 키즈 (Stray Kids)**
* **있지 (ITZY)**
* **엔믹스 (NMIXX)**
등 세계적인 인기를 자랑하는 그룹이 다수 소속되어 있으며, K팝의 글로벌한 발전에서 중심적인 역할을 계속해서 맡고 있습니다. 음악 사업 외에 배우 매니지먼트나 공연 사업도 하고 있습니다.');
        $input = new PublishAgencyInput(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
        );

        $status = ApprovalStatus::UnderReview;
        $agency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $CEO,
            $foundedIn,
            $description,
            $status,
        );

        $exName = new AgencyName('HYBE');
        $exCEO = new CEO('이재상');
        $exFoundedInt = new FoundedIn(new DateTimeImmutable('2005-02-01'));
        $exDescription = new Description('HYBE의 가장 큰 특징은 단순한 연예 기획사가 아니라 **\'음악 산업의 혁신\'**을 목표로 하는 라이프스타일 플랫폼 기업이라는 점입니다. BTS의 세계적인 성공을 기반으로 2021년에 현재의 사명으로 변경했습니다.');
        $publishedAgency = new Agency(
            $publishedAgencyIdentifier,
            $translation,
            $exName,
            $exCEO,
            $exFoundedInt,
            $exDescription,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($agencyIdentifier)
            ->andReturn($agency);
        $agencyRepository->shouldReceive('findById')
            ->once()
            ->with($publishedAgencyIdentifier)
            ->andReturn($publishedAgency);
        $agencyRepository->shouldReceive('save')
            ->once()
            ->with($publishedAgency)
            ->andReturn(null);
        $agencyRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($agency)
            ->andReturn(null);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyService->shouldReceive('existsApprovedButNotTranslatedAgency')
            ->once()
            ->with($agencyIdentifier, $publishedAgencyIdentifier)
            ->andReturn(false);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $publishedAgency = $publishAgency->process($input);
        $this->assertSame((string)$publishedAgencyIdentifier, (string)$publishedAgency->agencyIdentifier());
        $this->assertSame($translation->value, $publishedAgency->translation()->value);
        $this->assertSame((string)$name, (string)$publishedAgency->name());
        $this->assertSame((string)$CEO, (string)$publishedAgency->CEO());
        $this->assertSame($foundedIn->value(), $publishedAgency->foundedIn()->value());
        $this->assertSame((string)$description, (string)$publishedAgency->description());
    }

    /**
     * 正常系：正しく変更されたAgencyが公開されること（初めて公開する場合）.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     */
    public function testProcessForTheFirstTime(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $CEO = new CEO('J.Y. Park');
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description('### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다. HYBE, SM, YG엔터테인먼트와 함께 한국 연예계를 이끄는 **\'BIG4\'** 중 하나로 꼽힙니다.
**\'진실, 성실, 겸손\'**이라는 가치관을 매우 중시하며, 소속 아티스트의 노래나 댄스 실력뿐만 아니라 인성을 존중하는 육성 방침으로 알려져 있습니다. 이러한 철학은 박진영이 오디션 프로그램 등에서 보여주는 모습을 통해서도 널리 알려져 있습니다.
음악적인 면에서는 설립자인 박진영이 직접 프로듀서로서 많은 곡 작업에 참여하여, 대중에게 사랑받는 캐치한 히트곡을 수많이 만들어왔습니다.
---
### 주요 소속 아티스트
지금까지 **원더걸스(Wonder Girls)**, **2PM**, **미쓰에이(Miss A)**와 같이 K팝의 역사를 만들어 온 그룹들을 배출해왔습니다.
현재도
* **트와이스 (TWICE)**
* **스트레이 키즈 (Stray Kids)**
* **있지 (ITZY)**
* **엔믹스 (NMIXX)**
등 세계적인 인기를 자랑하는 그룹이 다수 소속되어 있으며, K팝의 글로벌한 발전에서 중심적인 역할을 계속해서 맡고 있습니다. 음악 사업 외에 배우 매니지먼트나 공연 사업도 하고 있습니다.');
        $input = new PublishAgencyInput(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
        );

        $status = ApprovalStatus::UnderReview;
        $agency = new DraftAgency(
            $agencyIdentifier,
            null,
            $editorIdentifier,
            $translation,
            $name,
            $CEO,
            $foundedIn,
            $description,
            $status,
        );

        $createdAgency = new Agency(
            $publishedAgencyIdentifier,
            $translation,
            $name,
            new CEO(''),
            null,
            new Description(''),
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($agencyIdentifier)
            ->andReturn($agency);
        $agencyRepository->shouldReceive('save')
            ->once()
            ->with($createdAgency)
            ->andReturn(null);
        $agencyRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($agency)
            ->andReturn(null);

        $agencyFactory = Mockery::mock(AgencyFactoryInterface::class);
        $agencyFactory->shouldReceive('create')
            ->once()
            ->with($translation, $name)
            ->andReturn($createdAgency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyService->shouldReceive('existsApprovedButNotTranslatedAgency')
            ->once()
            ->with($agencyIdentifier, $publishedAgencyIdentifier)
            ->andReturn(false);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyFactoryInterface::class, $agencyFactory);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $publishedAgency = $publishAgency->process($input);
        $this->assertSame((string)$publishedAgencyIdentifier, (string)$publishedAgency->agencyIdentifier());
        $this->assertSame($translation->value, $publishedAgency->translation()->value);
        $this->assertSame((string)$name, (string)$publishedAgency->name());
        $this->assertSame((string)$CEO, (string)$publishedAgency->CEO());
        $this->assertSame($foundedIn->value(), $publishedAgency->foundedIn()->value());
        $this->assertSame((string)$description, (string)$publishedAgency->description());
    }

    /**
     * 異常系：指定したIDに紐づくAgencyが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     */
    public function testWhenNotFoundAgency(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $input = new PublishAgencyInput(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($agencyIdentifier)
            ->andReturn(null);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->expectException(AgencyNotFoundException::class);
        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $publishAgency->process($input);
    }

    /**
     * 異常系：承認ステータスがUnderReview以外の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     */
    public function testInvalidStatus(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $CEO = new CEO('J.Y. Park');
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description('### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다. HYBE, SM, YG엔터테인먼트와 함께 한국 연예계를 이끄는 **\'BIG4\'** 중 하나로 꼽힙니다.
**\'진실, 성실, 겸손\'**이라는 가치관을 매우 중시하며, 소속 아티스트의 노래나 댄스 실력뿐만 아니라 인성을 존중하는 육성 방침으로 알려져 있습니다. 이러한 철학은 박진영이 오디션 프로그램 등에서 보여주는 모습을 통해서도 널리 알려져 있습니다.
음악적인 면에서는 설립자인 박진영이 직접 프로듀서로서 많은 곡 작업에 참여하여, 대중에게 사랑받는 캐치한 히트곡을 수많이 만들어왔습니다.
---
### 주요 소속 아티스트
지금까지 **원더걸스(Wonder Girls)**, **2PM**, **미쓰에이(Miss A)**와 같이 K팝의 역사를 만들어 온 그룹들을 배출해왔습니다.
현재도
* **트와이스 (TWICE)**
* **스트레이 키즈 (Stray Kids)**
* **있지 (ITZY)**
* **엔믹스 (NMIXX)**
등 세계적인 인기를 자랑하는 그룹이 다수 소속되어 있으며, K팝의 글로벌한 발전에서 중심적인 역할을 계속해서 맡고 있습니다. 음악 사업 외에 배우 매니지먼트나 공연 사업도 하고 있습니다.');
        $input = new PublishAgencyInput(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
        );

        $status = ApprovalStatus::Approved;
        $agency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $CEO,
            $foundedIn,
            $description,
            $status,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($agencyIdentifier)
            ->andReturn($agency);


        $agencyService = Mockery::mock(AgencyServiceInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->expectException(InvalidStatusException::class);
        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $publishAgency->process($input);
    }

    /**
     * 異常系：承認済みだが、翻訳が反映されていない承認済みの事務所がある場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     */
    public function testHasApprovedButNotTranslatedAgency(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $CEO = new CEO('J.Y. Park');
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description('### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다. HYBE, SM, YG엔터테인먼트와 함께 한국 연예계를 이끄는 **\'BIG4\'** 중 하나로 꼽힙니다.
**\'진실, 성실, 겸손\'**이라는 가치관을 매우 중시하며, 소속 아티스트의 노래나 댄스 실력뿐만 아니라 인성을 존중하는 육성 방침으로 알려져 있습니다. 이러한 철학은 박진영이 오디션 프로그램 등에서 보여주는 모습을 통해서도 널리 알려져 있습니다.
음악적인 면에서는 설립자인 박진영이 직접 프로듀서로서 많은 곡 작업에 참여하여, 대중에게 사랑받는 캐치한 히트곡을 수많이 만들어왔습니다.
---
### 주요 소속 아티스트
지금까지 **원더걸스(Wonder Girls)**, **2PM**, **미쓰에이(Miss A)**와 같이 K팝의 역사를 만들어 온 그룹들을 배출해왔습니다.
현재도
* **트와이스 (TWICE)**
* **스트레이 키즈 (Stray Kids)**
* **있지 (ITZY)**
* **엔믹스 (NMIXX)**
등 세계적인 인기를 자랑하는 그룹이 다수 소속되어 있으며, K팝의 글로벌한 발전에서 중심적인 역할을 계속해서 맡고 있습니다. 음악 사업 외에 배우 매니지먼트나 공연 사업도 하고 있습니다.');
        $input = new PublishAgencyInput(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
        );

        $status = ApprovalStatus::UnderReview;
        $agency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $CEO,
            $foundedIn,
            $description,
            $status,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($agencyIdentifier)
            ->andReturn($agency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyService->shouldReceive('existsApprovedButNotTranslatedAgency')
            ->once()
            ->with($agencyIdentifier, $publishedAgencyIdentifier)
            ->andReturn(true);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->expectException(ExistsApprovedButNotTranslatedAgencyException::class);
        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $publishAgency->process($input);
    }

    /**
     * 異常系：公開されている事務所情報が取得できない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     */
    public function testWhenNotFoundPublishedAgency(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $CEO = new CEO('J.Y. Park');
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description('### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다. HYBE, SM, YG엔터테인먼트와 함께 한국 연예계를 이끄는 **\'BIG4\'** 중 하나로 꼽힙니다.
**\'진실, 성실, 겸손\'**이라는 가치관을 매우 중시하며, 소속 아티스트의 노래나 댄스 실력뿐만 아니라 인성을 존중하는 육성 방침으로 알려져 있습니다. 이러한 철학은 박진영이 오디션 프로그램 등에서 보여주는 모습을 통해서도 널리 알려져 있습니다.
음악적인 면에서는 설립자인 박진영이 직접 프로듀서로서 많은 곡 작업에 참여하여, 대중에게 사랑받는 캐치한 히트곡을 수많이 만들어왔습니다.
---
### 주요 소속 아티스트
지금까지 **원더걸스(Wonder Girls)**, **2PM**, **미쓰에이(Miss A)**와 같이 K팝의 역사를 만들어 온 그룹들을 배출해왔습니다.
현재도
* **트와이스 (TWICE)**
* **스트레이 키즈 (Stray Kids)**
* **있지 (ITZY)**
* **엔믹스 (NMIXX)**
등 세계적인 인기를 자랑하는 그룹이 다수 소속되어 있으며, K팝의 글로벌한 발전에서 중심적인 역할을 계속해서 맡고 있습니다. 음악 사업 외에 배우 매니지먼트나 공연 사업도 하고 있습니다.');
        $input = new PublishAgencyInput(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
        );

        $status = ApprovalStatus::UnderReview;
        $agency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $CEO,
            $foundedIn,
            $description,
            $status,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($agencyIdentifier)
            ->andReturn($agency);
        $agencyRepository->shouldReceive('findById')
            ->once()
            ->with($publishedAgencyIdentifier)
            ->andReturn(null);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyService->shouldReceive('existsApprovedButNotTranslatedAgency')
            ->once()
            ->with($agencyIdentifier, $publishedAgencyIdentifier)
            ->andReturn(false);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->expectException(AgencyNotFoundException::class);
        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $publishAgency->process($input);
    }
}
