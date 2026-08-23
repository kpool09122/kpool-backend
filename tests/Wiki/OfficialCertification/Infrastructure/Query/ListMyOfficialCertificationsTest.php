<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\ListMyOfficialCertifications\ListMyOfficialCertificationsInput;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\ListMyOfficialCertifications\ListMyOfficialCertificationsInterface;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\ListMyOfficialCertifications\ListMyOfficialCertificationsOutput;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\OfficialCertification\Infrastructure\Query\ListMyOfficialCertifications;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListMyOfficialCertificationsTest extends TestCase
{
    public function test__construct(): void
    {
        $this->app->instance(PrincipalRepositoryInterface::class, Mockery::mock(PrincipalRepositoryInterface::class));
        $this->app->instance(PolicyEvaluatorInterface::class, Mockery::mock(PolicyEvaluatorInterface::class));

        $useCase = $this->app->make(ListMyOfficialCertificationsInterface::class);

        $this->assertInstanceOf(ListMyOfficialCertifications::class, $useCase);
    }

    #[Group('useDb')]
    public function testProcessListsOnlyCurrentAccountCertifications(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $otherAccountIdentifier = StrTestHelper::generateUuid();
        $this->registerAuthorizedPrincipal($principalIdentifier, true);
        $ownCertificationId = $this->insertCertification(CertificationStatus::PENDING, '2024-01-03 00:00:00', ownerAccountIdentifier: (string) $accountIdentifier);
        $this->insertCertification(CertificationStatus::APPROVED, '2024-01-04 00:00:00', ownerAccountIdentifier: $otherAccountIdentifier);

        $useCase = $this->app->make(ListMyOfficialCertificationsInterface::class);
        $output = new ListMyOfficialCertificationsOutput();

        $useCase->process(new ListMyOfficialCertificationsInput($principalIdentifier, $accountIdentifier, AccountCategory::TALENT, perPage: 10), $output);

        $items = $output->toArray()['officialCertifications'];
        $this->assertSame([$ownCertificationId], array_column($items, 'certificationIdentifier'));
        $this->assertSame(1, $output->toArray()['total']);
    }

    #[Group('useDb')]
    public function testProcessFiltersCurrentAccountCertificationsByStatus(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $this->registerAuthorizedPrincipal($principalIdentifier, true);
        $pendingCertificationId = $this->insertCertification(CertificationStatus::PENDING, '2024-01-01 00:00:00', ownerAccountIdentifier: (string) $accountIdentifier);
        $this->insertCertification(CertificationStatus::APPROVED, '2024-01-02 00:00:00', ownerAccountIdentifier: (string) $accountIdentifier);

        $useCase = $this->app->make(ListMyOfficialCertificationsInterface::class);
        $output = new ListMyOfficialCertificationsOutput();

        $useCase->process(new ListMyOfficialCertificationsInput($principalIdentifier, $accountIdentifier, AccountCategory::TALENT, CertificationStatus::PENDING, 10), $output);

        $items = $output->toArray()['officialCertifications'];
        $this->assertSame([$pendingCertificationId], array_column($items, 'certificationIdentifier'));
        $this->assertSame(['pending'], array_column($items, 'status'));
    }

    #[Group('useDb')]
    public function testProcessIncludesSameResponseShapeAsOperatorList(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $wikiIdentifier = StrTestHelper::generateUuid();
        $this->registerAuthorizedPrincipal($principalIdentifier, true);
        CreateAccount::create($accountIdentifier, [
            'email' => 'owner@example.com',
            'type' => 'corporate',
            'name' => 'Owner Account',
            'status' => 'active',
            'category' => 'talent',
        ]);
        CreateWiki::create($wikiIdentifier, ResourceType::TALENT->value, [
            'translation_set_identifier' => $translationSetIdentifier,
            'slug' => 'tl-chaeyoung',
            'language' => 'ko',
            'owner_account_id' => $accountIdentifier,
            'published_at' => '2024-01-01 00:00:00',
        ], [
            'name' => '채영',
            'normalized_name' => 'chaeyoung',
        ]);
        $certificationId = $this->insertCertification(
            CertificationStatus::PENDING,
            '2024-01-01 00:00:00',
            $translationSetIdentifier,
            $accountIdentifier,
        );

        $useCase = $this->app->make(ListMyOfficialCertificationsInterface::class);
        $output = new ListMyOfficialCertificationsOutput();

        $useCase->process(new ListMyOfficialCertificationsInput($principalIdentifier, new AccountIdentifier($accountIdentifier), AccountCategory::TALENT, perPage: 10), $output);

        $item = $output->toArray()['officialCertifications'][0];
        $this->assertSame($certificationId, $item['certificationIdentifier']);
        $this->assertSame($accountIdentifier, $item['ownerAccount']['accountIdentifier']);
        $this->assertSame($wikiIdentifier, $item['wikis'][0]['wikiIdentifier']);
    }

    #[Group('useDb')]
    public function testProcessWhenPolicyDenies(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $this->registerAuthorizedPrincipal($principalIdentifier, false);
        $this->insertCertification(CertificationStatus::PENDING, '2024-01-01 00:00:00', ownerAccountIdentifier: (string) $accountIdentifier);

        $useCase = $this->app->make(ListMyOfficialCertificationsInterface::class);
        $output = new ListMyOfficialCertificationsOutput();

        $this->expectException(DisallowedException::class);

        $useCase->process(new ListMyOfficialCertificationsInput($principalIdentifier, $accountIdentifier, AccountCategory::TALENT), $output);
    }

    private function registerAuthorizedPrincipal(PrincipalIdentifier $principalIdentifier, bool $allowed): void
    {
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()));
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->with($principalIdentifier)->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->with(Mockery::type(Principal::class), Action::OFFICIAL_CERTIFICATION_MY_READ, Mockery::on(
                fn (Resource $resource): bool => $resource->type() === ResourceType::TALENT
                    && $resource->requesterAccountCategory() === AccountCategory::TALENT,
            ))
            ->andReturn($allowed);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
    }

    private function insertCertification(
        CertificationStatus $status,
        string $requestedAt,
        ?string $translationSetIdentifier = null,
        ?string $ownerAccountIdentifier = null,
    ): string {
        $certificationId = StrTestHelper::generateUuid();
        DB::table('official_certifications')->insert([
            'id' => $certificationId,
            'resource_type' => ResourceType::TALENT->value,
            'translation_set_identifier' => $translationSetIdentifier ?? StrTestHelper::generateUuid(),
            'owner_account_id' => $ownerAccountIdentifier ?? StrTestHelper::generateUuid(),
            'status' => $status->value,
            'requested_at' => $requestedAt,
            'approved_at' => $status === CertificationStatus::APPROVED ? '2024-01-04 00:00:00' : null,
            'rejected_at' => $status === CertificationStatus::REJECTED ? '2024-01-05 00:00:00' : null,
            'created_at' => $requestedAt,
            'updated_at' => $requestedAt,
        ]);

        return $certificationId;
    }
}
