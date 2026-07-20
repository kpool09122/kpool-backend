<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisOutput;
use Tests\Helper\CreateDraftWiki;
use Tests\Helper\CreateImage;
use Tests\Helper\CreateWiki;
use Tests\TestCase;

class ListDraftWikisTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessFiltersByStatusSortedByEditedAtDesc(): void
    {
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f101', 'talent', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f901',
            'status' => ApprovalStatus::UnderReview->value,
            'edited_at' => '2026-05-01 00:00:00',
        ], [
            'name' => 'Alpha',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f102', 'group', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f902',
            'status' => ApprovalStatus::UnderReview->value,
            'edited_at' => '2026-05-03 00:00:00',
        ], [
            'name' => 'Beta',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f103', 'agency', [
            'status' => ApprovalStatus::Pending->value,
            'edited_at' => '2026-05-04 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f104', 'song', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f903',
            'status' => ApprovalStatus::UnderReview->value,
            'edited_at' => '2026-05-05 00:00:00',
        ], [
            'name' => 'Gamma',
        ]);

        $payload = $this->process(new ListDraftWikisInput(
            statuses: [ApprovalStatus::UnderReview],
            principalIdentifier: $this->defaultPrincipalIdentifier(),
        ))->toArray();

        $this->assertSame(1, $payload['current_page']);
        $this->assertSame(1, $payload['last_page']);
        $this->assertSame(3, $payload['total']);
        $this->assertSame(10, $payload['per_page']);
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f104',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f102',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
        ], array_column($payload['wikis'], 'wikiIdentifier'));
        $this->assertSame([
            ApprovalStatus::UnderReview->value,
            ApprovalStatus::UnderReview->value,
            ApprovalStatus::UnderReview->value,
        ], array_column($payload['wikis'], 'status'));
    }

    #[Group('useDb')]
    public function testProcessFiltersByStatuses(): void
    {
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f111', 'talent', [
            'status' => ApprovalStatus::UnderReview->value,
            'edited_at' => '2026-05-01 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f112', 'group', [
            'status' => ApprovalStatus::Pending->value,
            'edited_at' => '2026-05-02 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f113', 'song', [
            'status' => ApprovalStatus::Approved->value,
            'edited_at' => '2026-05-03 00:00:00',
        ]);

        $payload = $this->process(new ListDraftWikisInput(
            statuses: [ApprovalStatus::UnderReview, ApprovalStatus::Pending],
            principalIdentifier: $this->defaultPrincipalIdentifier(),
        ))->toArray();

        $this->assertSame(2, $payload['total']);
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f112',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f111',
        ], array_column($payload['wikis'], 'wikiIdentifier'));
        $this->assertSame([
            ApprovalStatus::Pending->value,
            ApprovalStatus::UnderReview->value,
        ], array_column($payload['wikis'], 'status'));
    }

    #[Group('useDb')]
    public function testProcessFiltersByTranslationSetIdentifierWhenSpecified(): void
    {
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f201', 'talent', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f401',
            'status' => ApprovalStatus::UnderReview->value,
            'edited_at' => '2026-05-01 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f202', 'talent', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f402',
            'status' => ApprovalStatus::UnderReview->value,
            'edited_at' => '2026-05-02 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f203', 'talent', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f401',
            'status' => ApprovalStatus::Pending->value,
            'edited_at' => '2026-05-03 00:00:00',
        ]);

        $payload = $this->process(new ListDraftWikisInput(
            statuses: [ApprovalStatus::UnderReview],
            principalIdentifier: $this->defaultPrincipalIdentifier(),
            translationSetIdentifier: new TranslationSetIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f401'),
        ))->toArray();

        $this->assertSame(1, $payload['total']);
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f201',
        ], array_column($payload['wikis'], 'wikiIdentifier'));
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f401',
        ], array_column($payload['wikis'], 'translationSetIdentifier'));
    }

    #[Group('useDb')]
    public function testProcessFiltersByResourceTypeWhenSpecified(): void
    {
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f301', 'talent', [
            'status' => ApprovalStatus::UnderReview->value,
            'edited_at' => '2026-05-01 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f302', 'group', [
            'status' => ApprovalStatus::UnderReview->value,
            'edited_at' => '2026-05-02 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f303', 'song', [
            'status' => ApprovalStatus::UnderReview->value,
            'edited_at' => '2026-05-03 00:00:00',
        ]);

        $payload = $this->process(new ListDraftWikisInput(
            statuses: [ApprovalStatus::UnderReview],
            principalIdentifier: $this->defaultPrincipalIdentifier(),
            resourceType: ResourceType::GROUP,
        ))->toArray();

        $this->assertSame(1, $payload['total']);
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f302',
        ], array_column($payload['wikis'], 'wikiIdentifier'));
        $this->assertSame([ResourceType::GROUP->value], array_column($payload['wikis'], 'resourceType'));
    }

    #[Group('useDb')]
    public function testProcessAppliesPerPage(): void
    {
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f401', 'talent', [
            'status' => ApprovalStatus::Pending->value,
            'edited_at' => '2026-05-01 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f402', 'talent', [
            'status' => ApprovalStatus::Pending->value,
            'edited_at' => '2026-05-02 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f403', 'talent', [
            'status' => ApprovalStatus::Pending->value,
            'edited_at' => '2026-05-03 00:00:00',
        ]);

        $payload = $this->process(new ListDraftWikisInput(
            statuses: [ApprovalStatus::Pending],
            principalIdentifier: $this->defaultPrincipalIdentifier(),
            perPage: 2,
        ))->toArray();

        $this->assertCount(2, $payload['wikis']);
        $this->assertSame(2, $payload['per_page']);
        $this->assertSame(2, $payload['last_page']);
        $this->assertSame(3, $payload['total']);
    }

    #[Group('useDb')]
    public function testProcessAuthorizesAdminListWithReadAction(): void
    {
        $principalIdentifier = new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f711');
        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f712'),
            null,
            [],
            [],
        );

        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f611', 'group', [
            'status' => ApprovalStatus::UnderReview->value,
            'editor_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f713',
        ], [
            'agency_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f714',
        ]);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->with($principalIdentifier)->andReturn($principal);
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->once()
            ->with($principal, Action::READ, Mockery::on(
                static fn (Resource $resource): bool => $resource->type() === ResourceType::GROUP
                    && $resource->agencyId() === '01965bb2-bcc9-7c6f-8b90-89f7f217f714'
                    && $resource->groupIds() === ['01965bb2-bcc9-7c6f-8b90-89f7f217f611']
                    && $resource->editorId() === '01965bb2-bcc9-7c6f-8b90-89f7f217f713'
            ))
            ->andReturn(true);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);

        $payload = $this->process(new ListDraftWikisInput(
            statuses: [ApprovalStatus::UnderReview],
            principalIdentifier: $principalIdentifier,
        ))->toArray();

        $this->assertSame(1, $payload['total']);
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f611', $payload['wikis'][0]['wikiIdentifier']);
    }

    #[Group('useDb')]
    public function testProcessThrowsDisallowedWhenReadPolicyDeniesAdminList(): void
    {
        $principalIdentifier = new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f721');
        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f722'),
            null,
            [],
            [],
        );

        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f621', 'talent', [
            'status' => ApprovalStatus::UnderReview->value,
        ]);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->with($principalIdentifier)->andReturn($principal);
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(false);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);

        $this->expectException(DisallowedException::class);

        $this->process(new ListDraftWikisInput(
            statuses: [ApprovalStatus::UnderReview],
            principalIdentifier: $principalIdentifier,
        ));
    }

    #[Group('useDb')]
    public function testProcessThrowsPrincipalNotFoundWhenAdminPrincipalIsMissing(): void
    {
        $principalIdentifier = new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f731');
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f631', 'talent', [
            'status' => ApprovalStatus::UnderReview->value,
        ]);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->with($principalIdentifier)->andReturn(null);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);

        $this->expectException(PrincipalNotFoundException::class);

        $this->process(new ListDraftWikisInput(
            statuses: [ApprovalStatus::UnderReview],
            principalIdentifier: $principalIdentifier,
        ));
    }

    #[Group('useDb')]
    public function testProcessReturnsDraftWikiMetadata(): void
    {
        CreateWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f502', 'group', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f601',
            'slug' => 'gr-twice',
            'language' => 'ko',
        ]);

        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f501', 'group', [
            'published_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f502',
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f601',
            'slug' => 'gr-twice',
            'language' => 'ko',
            'theme_color' => '#ff3366',
            'title' => 'TWICE Draft Wiki',
            'meta_description' => 'Draft profile for TWICE.',
            'keywords' => json_encode(['TWICE', 'draft']),
            'status' => ApprovalStatus::Approved->value,
            'edited_at' => '2026-05-01 00:00:00',
            'approved_at' => '2026-05-02 00:00:00',
            'translated_at' => '2026-05-03 00:00:00',
            'merged_at' => '2026-05-04 00:00:00',
            'updated_at' => '2026-05-05 00:00:00',
        ], [
            'name' => 'TWICE',
            'normalized_name' => 'twice',
        ]);

        $payload = $this->process(new ListDraftWikisInput(
            statuses: [ApprovalStatus::Approved],
            principalIdentifier: $this->defaultPrincipalIdentifier(),
            translationSetIdentifier: new TranslationSetIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f601'),
        ))->toArray();

        $this->assertSame([
            'wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f501',
            'publishedWikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f502',
            'translationSetIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f601',
            'slug' => 'gr-twice',
            'language' => 'ko',
            'resourceType' => 'group',
            'themeColor' => '#ff3366',
            'fontStyle' => null,
            'title' => 'TWICE Draft Wiki',
            'metaDescription' => 'Draft profile for TWICE.',
            'keywords' => ['TWICE', 'draft'],
            'imageIdentifier' => null,
            'imageUrl' => null,
            'imageAltText' => null,
            'isHidden' => null,
            'status' => ApprovalStatus::Approved->value,
            'rejectionReason' => null,
            'name' => 'TWICE',
            'normalizedName' => 'twice',
            'editedAt' => '2026-05-01T00:00:00+00:00',
            'approvedAt' => '2026-05-02T00:00:00+00:00',
            'translatedAt' => '2026-05-03T00:00:00+00:00',
            'mergedAt' => '2026-05-04T00:00:00+00:00',
        ], $payload['wikis'][0]);
    }

    #[Group('useDb')]
    public function testProcessReturnsDraftWikiImageFieldsWhenImageExists(): void
    {
        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f801', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f901',
            'image_path' => '/images/test/card.jpg',
            'alt_text' => 'Wiki card image',
            'is_hidden' => true,
        ]);

        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f802', 'talent', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f901',
            'image_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f801',
            'status' => ApprovalStatus::Pending->value,
        ]);

        $payload = $this->process(new ListDraftWikisInput(
            statuses: [ApprovalStatus::Pending],
            principalIdentifier: $this->defaultPrincipalIdentifier(),
            translationSetIdentifier: new TranslationSetIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f901'),
        ))->toArray();

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f801', $payload['wikis'][0]['imageIdentifier']);
        $this->assertSame('http://127.0.0.1:8080/images/test/card.jpg', $payload['wikis'][0]['imageUrl']);
        $this->assertSame('Wiki card image', $payload['wikis'][0]['imageAltText']);
        $this->assertTrue($payload['wikis'][0]['isHidden']);
    }

    private function listDraftWikis(): ListDraftWikisInterface
    {
        return $this->app->make(ListDraftWikisInterface::class);
    }

    private function process(ListDraftWikisInput $input): ListDraftWikisOutput
    {
        $this->allowReadPoliciesForDefaultPrincipal($input);

        $output = new ListDraftWikisOutput();
        $this->listDraftWikis()->process($input, $output);

        return $output;
    }

    private function defaultPrincipalIdentifier(): PrincipalIdentifier
    {
        return new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f700');
    }

    private function allowReadPoliciesForDefaultPrincipal(ListDraftWikisInput $input): void
    {
        if ((string) $input->principalIdentifier() !== (string) $this->defaultPrincipalIdentifier()) {
            return;
        }

        $principal = new Principal(
            $this->defaultPrincipalIdentifier(),
            new IdentityIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f701'),
            null,
            [],
            [],
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with(Mockery::on(fn (PrincipalIdentifier $principalIdentifier): bool => (string) $principalIdentifier === (string) $this->defaultPrincipalIdentifier()))
            ->andReturn($principal);
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->andReturn(true);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
    }
}
