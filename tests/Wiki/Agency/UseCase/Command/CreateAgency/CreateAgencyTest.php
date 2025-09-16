<?php

namespace Tests\Wiki\Agency\UseCase\Command\CreateAgency;

use Businesses\Shared\Service\Ulid\UlidValidator;
use Businesses\Wiki\Agency\Domain\Entity\Agency;
use Businesses\Wiki\Agency\Domain\Factory\AgencyFactoryInterface;
use Businesses\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Businesses\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Businesses\Wiki\Agency\Domain\ValueObject\AgencyName;
use Businesses\Wiki\Agency\Domain\ValueObject\CEO;
use Businesses\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Businesses\Wiki\Agency\UseCase\Command\CreateAgency\CreateAgency;
use Businesses\Wiki\Agency\UseCase\Command\CreateAgency\CreateAgencyInput;
use Businesses\Wiki\Agency\UseCase\Command\CreateAgency\CreateAgencyInterface;
use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateAgencyTest extends TestCase
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
        $createAgency = $this->app->make(CreateAgencyInterface::class);
        $this->assertInstanceOf(CreateAgency::class, $createAgency);
    }

    /**
     * 正常系：正しくAgency Entityが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $name = new AgencyName('JYP엔터테인먼트');
        $CEO = new CEO('J.Y. Park');
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new \Businesses\Wiki\Agency\Domain\ValueObject\Description('### JYP엔터테인먼트 (JYP Entertainment)
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
        $input = new CreateAgencyInput(
            $name,
            $CEO,
            $foundedIn,
            $description,
        );

        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $agency = new Agency(
            $agencyIdentifier,
            $name,
            $CEO,
            $foundedIn,
            $description,
        );
        $agencyFactory = Mockery::mock(AgencyFactoryInterface::class);
        $agencyFactory->shouldReceive('create')
            ->once()
            ->with($name)
            ->andReturn($agency);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('save')
            ->once()
            ->with($agency)
            ->andReturn(null);
        $agencyRepository->shouldReceive('findById')
            ->once()
            ->with($agencyIdentifier)
            ->andReturn($agency);

        $this->app->instance(AgencyFactoryInterface::class, $agencyFactory);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $createAgency = $this->app->make(CreateAgencyInterface::class);
        $agency = $createAgency->process($input);
        $this->assertTrue(UlidValidator::isValid((string)$agency->agencyIdentifier()));
        $this->assertSame((string)$name, (string)$agency->name());
        $this->assertSame((string)$CEO, (string)$agency->CEO());
        $this->assertSame($foundedIn->value(), $agency->foundedIn()->value());
        $this->assertSame((string)$description, (string)$agency->description());
    }
}
