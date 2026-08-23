<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\ListOfficialCertifications\ListOfficialCertificationsInput;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\ListOfficialCertifications\ListOfficialCertificationsInterface;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\ListOfficialCertifications\ListOfficialCertificationsOutput;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\OfficialCertification\Infrastructure\Query\ListOfficialCertifications;
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

class ListOfficialCertificationsTest extends TestCase
{
    public function test__construct(): void
    {
        $this->app->instance(PrincipalRepositoryInterface::class, Mockery::mock(PrincipalRepositoryInterface::class));
        $this->app->instance(PolicyEvaluatorInterface::class, Mockery::mock(PolicyEvaluatorInterface::class));

        $useCase = $this->app->make(ListOfficialCertificationsInterface::class);

        $this->assertInstanceOf(ListOfficialCertifications::class, $useCase);
    }

    #[Group('useDb')]
    public function testProcessListsAllStatusesOrderedByRequestedAt(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $this->registerAuthorizedPrincipal($principalIdentifier, true);
        $oldCertificationId = $this->insertCertification(CertificationStatus::APPROVED, '2024-01-01 00:00:00');
        $newCertificationId = $this->insertCertification(CertificationStatus::PENDING, '2024-01-03 00:00:00');
        $middleCertificationId = $this->insertCertification(CertificationStatus::REJECTED, '2024-01-02 00:00:00');

        $useCase = $this->app->make(ListOfficialCertificationsInterface::class);
        $output = new ListOfficialCertificationsOutput();

        $useCase->process(new ListOfficialCertificationsInput($principalIdentifier, perPage: 10), $output);

        $items = $output->toArray()['officialCertifications'];
        $this->assertSame([$newCertificationId, $middleCertificationId, $oldCertificationId], array_column($items, 'certificationIdentifier'));
        $this->assertSame(3, $output->toArray()['total']);
    }

    #[Group('useDb')]
    public function testProcessFiltersBySingleStatus(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $this->registerAuthorizedPrincipal($principalIdentifier, true);
        $pendingCertificationId = $this->insertCertification(CertificationStatus::PENDING, '2024-01-01 00:00:00');
        $this->insertCertification(CertificationStatus::APPROVED, '2024-01-02 00:00:00');

        $useCase = $this->app->make(ListOfficialCertificationsInterface::class);
        $output = new ListOfficialCertificationsOutput();

        $useCase->process(new ListOfficialCertificationsInput($principalIdentifier, CertificationStatus::PENDING, 10), $output);

        $items = $output->toArray()['officialCertifications'];
        $this->assertSame([$pendingCertificationId], array_column($items, 'certificationIdentifier'));
        $this->assertSame(['pending'], array_column($items, 'status'));
    }

    #[Group('useDb')]
    public function testProcessIncludesOwnerAccountAndTranslationSetWikis(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $this->registerAuthorizedPrincipal($principalIdentifier, true);
        $accountIdentifier = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $wikiIdentifier = StrTestHelper::generateUuid();
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
        CreateWiki::create(StrTestHelper::generateUuid(), ResourceType::TALENT->value, [
            'translation_set_identifier' => StrTestHelper::generateUuid(),
        ]);
        $certificationId = $this->insertCertification(
            CertificationStatus::PENDING,
            '2024-01-01 00:00:00',
            $translationSetIdentifier,
            $accountIdentifier,
        );

        $useCase = $this->app->make(ListOfficialCertificationsInterface::class);
        $output = new ListOfficialCertificationsOutput();

        $useCase->process(new ListOfficialCertificationsInput($principalIdentifier, perPage: 10), $output);

        $item = $output->toArray()['officialCertifications'][0];
        $this->assertSame($certificationId, $item['certificationIdentifier']);
        $this->assertSame([
            'accountIdentifier' => $accountIdentifier,
            'email' => 'owner@example.com',
            'type' => 'corporate',
            'name' => 'Owner Account',
            'status' => 'active',
            'category' => 'talent',
        ], $item['ownerAccount']);
        $this->assertCount(1, $item['wikis']);
        $this->assertSame($wikiIdentifier, $item['wikis'][0]['wikiIdentifier']);
        $this->assertSame($translationSetIdentifier, $item['wikis'][0]['translationSetIdentifier']);
        $this->assertSame('tl-chaeyoung', $item['wikis'][0]['slug']);
        $this->assertSame('채영', $item['wikis'][0]['name']);
    }

    #[Group('useDb')]
    public function testProcessWhenPolicyDenies(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $this->registerAuthorizedPrincipal($principalIdentifier, false);
        $this->insertCertification(CertificationStatus::PENDING, '2024-01-01 00:00:00');

        $useCase = $this->app->make(ListOfficialCertificationsInterface::class);
        $output = new ListOfficialCertificationsOutput();

        $this->expectException(DisallowedException::class);

        $useCase->process(new ListOfficialCertificationsInput($principalIdentifier), $output);
    }

    private function registerAuthorizedPrincipal(PrincipalIdentifier $principalIdentifier, bool $allowed): void
    {
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()));
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->with($principalIdentifier)->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->with(Mockery::type(Principal::class), Action::OFFICIAL_CERTIFICATION_READ, Mockery::type(Resource::class))
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
