<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedWikis\ListRelatedWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedWikis\ListRelatedWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedWikis\ListRelatedWikisOutput;
use Tests\Helper\CreateWiki;
use Tests\TestCase;

class ListRelatedWikisTest extends TestCase
{
    private const PRINCIPAL_ID = '01965bb2-bcc9-7c6f-8b90-89f7f217ffff';

    #[Group('useDb')]
    public function testGeneralAccountReceivesEmptyForAgencySource(): void
    {
        $translationSetIdentifier = '01965bb2-bcc9-7c6f-8b90-89f7f217f001';
        $this->createAgency('01965bb2-bcc9-7c6f-8b90-89f7f217f101', $translationSetIdentifier, 'ag-jyp', 'JYP Entertainment', 'jyp entertainment');
        $this->createGroup('01965bb2-bcc9-7c6f-8b90-89f7f217f102', 'gr-twice', 'TWICE', 'twice', '01965bb2-bcc9-7c6f-8b90-89f7f217f101');
        $this->createSong('01965bb2-bcc9-7c6f-8b90-89f7f217f103', 'sg-tt', 'TT', 'tt', '01965bb2-bcc9-7c6f-8b90-89f7f217f101');
        $this->createTalent('01965bb2-bcc9-7c6f-8b90-89f7f217f104', 'tl-momo', 'Momo', 'momo', [], '01965bb2-bcc9-7c6f-8b90-89f7f217f101');
        $this->createGroup('01965bb2-bcc9-7c6f-8b90-89f7f217f105', 'gr-unrelated', 'Unrelated', 'unrelated');
        $this->mockAuthorization(true);

        $payload = $this->process(ResourceType::AGENCY, $translationSetIdentifier, AccountCategory::GENERAL)->toArray();

        $this->assertSame([], $payload['wikis']);
    }

    #[Group('useDb')]
    public function testAgencyAccountDoesNotReceiveTalentForAgencySource(): void
    {
        $translationSetIdentifier = '01965bb2-bcc9-7c6f-8b90-89f7f217f201';
        $this->createAgency('01965bb2-bcc9-7c6f-8b90-89f7f217f201', $translationSetIdentifier, 'ag-jyp', 'JYP Entertainment', 'jyp entertainment');
        $this->createGroup('01965bb2-bcc9-7c6f-8b90-89f7f217f202', 'gr-twice', 'TWICE', 'twice', '01965bb2-bcc9-7c6f-8b90-89f7f217f201');
        $this->createTalent('01965bb2-bcc9-7c6f-8b90-89f7f217f203', 'tl-momo', 'Momo', 'momo', [], '01965bb2-bcc9-7c6f-8b90-89f7f217f201');
        $this->mockAuthorization(true);

        $payload = $this->process(ResourceType::AGENCY, $translationSetIdentifier, AccountCategory::AGENCY)->toArray();

        $this->assertSame(['gr-twice'], array_column($payload['wikis'], 'slug'));
    }

    #[Group('useDb')]
    public function testGeneralAccountReceivesEmptyForTalentSource(): void
    {
        $translationSetIdentifier = '01965bb2-bcc9-7c6f-8b90-89f7f217f301';
        $this->createAgency('01965bb2-bcc9-7c6f-8b90-89f7f217f302', '01965bb2-bcc9-7c6f-8b90-89f7f217f3aa', 'ag-jyp', 'JYP Entertainment', 'jyp entertainment');
        $this->createGroup('01965bb2-bcc9-7c6f-8b90-89f7f217f303', 'gr-twice', 'TWICE', 'twice');
        $this->createTalent('01965bb2-bcc9-7c6f-8b90-89f7f217f301', 'tl-momo', 'Momo', 'momo', ['01965bb2-bcc9-7c6f-8b90-89f7f217f303'], '01965bb2-bcc9-7c6f-8b90-89f7f217f302', $translationSetIdentifier);
        $this->createSong('01965bb2-bcc9-7c6f-8b90-89f7f217f304', 'sg-tt', 'TT', 'tt', null, [], ['01965bb2-bcc9-7c6f-8b90-89f7f217f301']);
        $this->mockAuthorization(true);

        $payload = $this->process(ResourceType::TALENT, $translationSetIdentifier, AccountCategory::GENERAL)->toArray();

        $this->assertSame([], $payload['wikis']);
    }

    #[Group('useDb')]
    public function testTalentAccountReceivesEmptyForTalentSource(): void
    {
        $translationSetIdentifier = '01965bb2-bcc9-7c6f-8b90-89f7f217f401';
        $this->createTalent('01965bb2-bcc9-7c6f-8b90-89f7f217f401', 'tl-momo', 'Momo', 'momo', [], null, $translationSetIdentifier);
        $this->mockAuthorization(true);

        $payload = $this->process(ResourceType::TALENT, $translationSetIdentifier, AccountCategory::TALENT)->toArray();

        $this->assertSame([], $payload['wikis']);
    }

    #[Group('useDb')]
    public function testProcessThrowsWikiNotFoundExceptionWhenSourceDoesNotExist(): void
    {
        $this->mockAuthorization(true);
        $this->expectException(WikiNotFoundException::class);

        $this->process(ResourceType::AGENCY, '01965bb2-bcc9-7c6f-8b90-89f7f217f501', AccountCategory::GENERAL);
    }

    #[Group('useDb')]
    public function testProcessThrowsDisallowedExceptionWhenPolicyDenies(): void
    {
        $translationSetIdentifier = '01965bb2-bcc9-7c6f-8b90-89f7f217f601';
        $this->createAgency('01965bb2-bcc9-7c6f-8b90-89f7f217f601', $translationSetIdentifier, 'ag-jyp', 'JYP Entertainment', 'jyp entertainment');
        $this->mockAuthorization(false);
        $this->expectException(DisallowedException::class);

        $this->process(ResourceType::AGENCY, $translationSetIdentifier, AccountCategory::GENERAL);
    }

    private function process(ResourceType $resourceType, string $translationSetIdentifier, AccountCategory $accountCategory): ListRelatedWikisOutput
    {
        $output = new ListRelatedWikisOutput();
        $this->app->make(ListRelatedWikisInterface::class)->process(
            new ListRelatedWikisInput(
                $resourceType,
                new TranslationSetIdentifier($translationSetIdentifier),
                new PrincipalIdentifier(self::PRINCIPAL_ID),
                $accountCategory,
            ),
            $output,
        );

        return $output;
    }

    private function mockAuthorization(bool $allowed): void
    {
        $principal = new Principal(
            new PrincipalIdentifier(self::PRINCIPAL_ID),
            new IdentityIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217fffe'),
            new DelegationIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217fffd'),
        );
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        $repository->shouldReceive('findById')->andReturn($principal);
        $this->app->instance(PrincipalRepositoryInterface::class, $repository);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->andReturn($allowed);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
    }

    private function createAgency(string $wikiId, string $translationSetIdentifier, string $slug, string $name, string $normalizedName): void
    {
        CreateWiki::create($wikiId, 'agency', [
            'translation_set_identifier' => $translationSetIdentifier,
            'slug' => $slug,
            'language' => 'ko',
            'published_at' => '2026-04-01 00:00:00',
        ], [
            'name' => $name,
            'normalized_name' => $normalizedName,
        ]);
    }

    private function createGroup(string $wikiId, string $slug, string $name, string $normalizedName, ?string $agencyIdentifier = null): void
    {
        CreateWiki::create($wikiId, 'group', [
            'slug' => $slug,
            'language' => 'ko',
            'published_at' => '2026-04-01 00:00:00',
        ], [
            'name' => $name,
            'normalized_name' => $normalizedName,
            'agency_identifier' => $agencyIdentifier,
        ]);
    }

    /** @param list<string> $groupIdentifiers */
    private function createTalent(string $wikiId, string $slug, string $name, string $normalizedName, array $groupIdentifiers, ?string $agencyIdentifier = null, ?string $translationSetIdentifier = null): void
    {
        CreateWiki::create($wikiId, 'talent', [
            'translation_set_identifier' => $translationSetIdentifier ?? '01965bb2-bcc9-7c6f-8b90-89f7f217f999',
            'slug' => $slug,
            'language' => 'ko',
            'published_at' => '2026-04-01 00:00:00',
        ], [
            'name' => $name,
            'normalized_name' => $normalizedName,
            'agency_identifier' => $agencyIdentifier,
            'group_identifiers' => json_encode($groupIdentifiers),
        ]);
    }

    /**
     * @param list<string> $groupIdentifiers
     * @param list<string> $talentIdentifiers
     */
    private function createSong(string $wikiId, string $slug, string $name, string $normalizedName, ?string $agencyIdentifier = null, array $groupIdentifiers = [], array $talentIdentifiers = []): void
    {
        CreateWiki::create($wikiId, 'song', [
            'slug' => $slug,
            'language' => 'ko',
            'published_at' => '2026-04-01 00:00:00',
        ], [
            'name' => $name,
            'normalized_name' => $normalizedName,
            'agency_identifier' => $agencyIdentifier,
            'group_identifiers' => json_encode($groupIdentifiers),
            'talent_identifiers' => json_encode($talentIdentifiers),
        ]);
    }
}
