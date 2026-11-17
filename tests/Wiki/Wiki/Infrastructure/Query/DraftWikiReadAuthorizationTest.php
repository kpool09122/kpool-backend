<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\GetAgencyDraftWiki\GetAgencyDraftWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetAgencyDraftWiki\GetAgencyDraftWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupDraftWiki\GetGroupDraftWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupDraftWiki\GetGroupDraftWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\GetSongDraftWiki\GetSongDraftWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetSongDraftWiki\GetSongDraftWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentDraftWiki\GetTalentDraftWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentDraftWiki\GetTalentDraftWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisOutput;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateDraftReadPrincipal;
use Tests\Helper\CreateDraftWiki;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class DraftWikiReadAuthorizationTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function resourceTypes(): array
    {
        return ['agency' => ['agency'], 'group' => ['group'], 'song' => ['song'], 'talent' => ['talent']];
    }

    #[DataProvider('resourceTypes')]
    public function testPrincipalWithoutReadPolicyCannotFetchDraftById(string $type): void
    {
        $principalIdentifier = CreateDraftReadPrincipal::create(allow: false);
        $wikiIdentifier = $this->createDraft($type);
        $output = new ListDraftWikisOutput();
        $this->app()->make(ListDraftWikisInterface::class)->process(new ListDraftWikisInput(
            statuses: [ApprovalStatus::Pending],
            principalIdentifier: $principalIdentifier,
        ), $output);
        self::assertSame(0, $output->toArray()['total']);

        $this->expectException(DisallowedException::class);
        $this->process($type, $wikiIdentifier, $principalIdentifier);
    }

    #[DataProvider('resourceTypes')]
    public function testReadGrantAllowsListAndIndividualFetch(string $type): void
    {
        $principalIdentifier = CreateDraftReadPrincipal::create();
        $wikiIdentifier = $this->createDraft($type);
        $output = new ListDraftWikisOutput();
        $this->app()->make(ListDraftWikisInterface::class)->process(new ListDraftWikisInput(
            statuses: [ApprovalStatus::Pending],
            principalIdentifier: $principalIdentifier,
        ), $output);
        self::assertSame([(string) $wikiIdentifier], array_column($output->toArray()['wikis'], 'wikiIdentifier'));
        self::assertSame((string) $wikiIdentifier, $this->process($type, $wikiIdentifier, $principalIdentifier)->wikiIdentifier());
    }

    #[DataProvider('resourceTypes')]
    public function testExplicitDenyOverridesReadGrant(string $type): void
    {
        $principalIdentifier = CreateDraftReadPrincipal::create(deny: true);
        $wikiIdentifier = $this->createDraft($type);
        $this->expectException(DisallowedException::class);
        $this->process($type, $wikiIdentifier, $principalIdentifier);
    }

    #[DataProvider('resourceTypes')]
    public function testMissingDraftRemainsNotFound(string $type): void
    {
        $principalIdentifier = CreateDraftReadPrincipal::create();
        $this->expectException(WikiNotFoundException::class);
        $this->process($type, new DraftWikiIdentifier(StrTestHelper::generateUuid()), $principalIdentifier);
    }

    #[DataProvider('resourceTypes')]
    public function testWrongResourceTypeRemainsNotFound(string $type): void
    {
        $principalIdentifier = CreateDraftReadPrincipal::create();
        $wikiIdentifier = $this->createDraft($type === 'agency' ? 'group' : 'agency');
        $this->expectException(WikiNotFoundException::class);
        $this->process($type, $wikiIdentifier, $principalIdentifier);
    }

    #[DataProvider('resourceTypes')]
    public function testMissingPrincipalIsRejected(string $type): void
    {
        $wikiIdentifier = $this->createDraft($type);
        $this->expectException(PrincipalNotFoundException::class);
        $this->process($type, $wikiIdentifier, new PrincipalIdentifier(StrTestHelper::generateUuid()));
    }

    #[DataProvider('resourceTypes')]
    public function testSingletonReloadsSuspendedPrincipal(string $type): void
    {
        $principalIdentifier = CreateDraftReadPrincipal::create();
        $wikiIdentifier = $this->createDraft($type);
        self::assertSame((string) $wikiIdentifier, $this->process($type, $wikiIdentifier, $principalIdentifier)->wikiIdentifier());
        DB::table('wiki_principals')->where('id', (string) $principalIdentifier)->update(['status' => 'suspended']);
        $this->expectException(DisallowedException::class);
        $this->process($type, $wikiIdentifier, $principalIdentifier);
    }

    #[DataProvider('resourceTypes')]
    public function testSingletonReloadsRevokedReadPolicy(string $type): void
    {
        $principalIdentifier = CreateDraftReadPrincipal::create();
        $wikiIdentifier = $this->createDraft($type);
        self::assertSame((string) $wikiIdentifier, $this->process($type, $wikiIdentifier, $principalIdentifier)->wikiIdentifier());
        DB::table('wiki_principal_group_memberships')->where('principal_id', (string) $principalIdentifier)->delete();
        $this->expectException(DisallowedException::class);
        $this->process($type, $wikiIdentifier, $principalIdentifier);
    }

    #[DataProvider('resourceTypes')]
    public function testAccountAndDelegationPrincipalDoNotInheritPreviousGrant(string $type): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);
        $allowed = CreateDraftReadPrincipal::create(identityIdentifier: $identityIdentifier);
        $otherAccount = CreateDraftReadPrincipal::create(allow: false, identityIdentifier: $identityIdentifier, accountIdentifier: $accountIdentifier);
        $delegated = CreateDraftReadPrincipal::create(allow: false, identityIdentifier: $identityIdentifier, delegationIdentifier: StrTestHelper::generateUuid());
        $wikiIdentifier = $this->createDraft($type);
        self::assertSame((string) $wikiIdentifier, $this->process($type, $wikiIdentifier, $allowed)->wikiIdentifier());
        foreach ([$otherAccount, $delegated] as $principalIdentifier) {
            try {
                $this->process($type, $wikiIdentifier, $principalIdentifier);
                self::fail('Another account/delegation principal inherited the previous READ grant.');
            } catch (DisallowedException $exception) {
                self::assertSame(DisallowedException::class, $exception::class);
            }
        }
    }

    #[DataProvider('resourceTypes')]
    public function testAgencyConditionUsesTargetDraftResource(string $type): void
    {
        $agencyId = StrTestHelper::generateUuid();
        CreateWiki::create($agencyId, 'agency');
        $wikiIdentifier = $type === 'agency'
            ? new DraftWikiIdentifier($agencyId)
            : new DraftWikiIdentifier(StrTestHelper::generateUuid());
        CreateDraftWiki::create((string) $wikiIdentifier, $type, [], $type === 'agency' ? [] : ['agency_identifier' => $agencyId]);
        $allowed = CreateDraftReadPrincipal::create(conditionKey: 'resource:agencyId', conditionValue: $agencyId);
        self::assertSame((string) $wikiIdentifier, $this->process($type, $wikiIdentifier, $allowed)->wikiIdentifier());
        $unrelated = CreateDraftReadPrincipal::create(conditionKey: 'resource:agencyId', conditionValue: StrTestHelper::generateUuid());
        $this->expectException(DisallowedException::class);
        $this->process($type, $wikiIdentifier, $unrelated);
    }

    /** @return array<string, array{string, string}> */
    public static function relatedResources(): array
    {
        return [
            'group itself' => ['group', 'group'],
            'talent itself' => ['talent', 'talent'],
            'talent groups' => ['talent', 'group'],
            'song groups' => ['song', 'group'],
            'song talents' => ['song', 'talent'],
        ];
    }

    #[DataProvider('relatedResources')]
    public function testRelatedResourceConditionUsesDraftRelationships(string $type, string $relatedType): void
    {
        $wikiIdentifier = $this->createDraft($type);
        $relatedId = (string) $wikiIdentifier;
        if ($type !== $relatedType) {
            $relatedId = StrTestHelper::generateUuid();
            CreateWiki::create($relatedId, $relatedType);
            DB::table('draft_wiki_' . $type . '_basic_' . $relatedType . 's')->insert([
                'wiki_id' => (string) $wikiIdentifier,
                $relatedType . '_identifier' => $relatedId,
            ]);
        }
        $conditionKey = 'resource:' . $relatedType . 'Id';
        $allowed = CreateDraftReadPrincipal::create(conditionKey: $conditionKey, conditionValue: $relatedId);
        self::assertSame((string) $wikiIdentifier, $this->process($type, $wikiIdentifier, $allowed)->wikiIdentifier());
        $unrelated = CreateDraftReadPrincipal::create(conditionKey: $conditionKey, conditionValue: StrTestHelper::generateUuid());
        $this->expectException(DisallowedException::class);
        $this->process($type, $wikiIdentifier, $unrelated);
    }

    #[DataProvider('resourceTypes')]
    public function testEditorConditionUsesDraftEditor(string $type): void
    {
        $editorId = StrTestHelper::generateUuid();
        $wikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        CreateDraftWiki::create((string) $wikiIdentifier, $type, ['editor_id' => $editorId]);
        $allowed = CreateDraftReadPrincipal::create(conditionKey: 'resource:editorId', conditionValue: $editorId);
        self::assertSame((string) $wikiIdentifier, $this->process($type, $wikiIdentifier, $allowed)->wikiIdentifier());
        $unrelated = CreateDraftReadPrincipal::create(conditionKey: 'resource:editorId', conditionValue: StrTestHelper::generateUuid());
        $this->expectException(DisallowedException::class);
        $this->process($type, $wikiIdentifier, $unrelated);
    }

    private function createDraft(string $type): DraftWikiIdentifier
    {
        $wikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        CreateDraftWiki::create((string) $wikiIdentifier, $type);

        return $wikiIdentifier;
    }

    private function process(string $type, DraftWikiIdentifier $wikiIdentifier, PrincipalIdentifier $principalIdentifier): DraftWikiReadModel
    {
        return match ($type) {
            'agency' => $this->app()->make(GetAgencyDraftWikiInterface::class)->process(new GetAgencyDraftWikiInput($wikiIdentifier, $principalIdentifier)),
            'group' => $this->app()->make(GetGroupDraftWikiInterface::class)->process(new GetGroupDraftWikiInput($wikiIdentifier, $principalIdentifier)),
            'song' => $this->app()->make(GetSongDraftWikiInterface::class)->process(new GetSongDraftWikiInput($wikiIdentifier, $principalIdentifier)),
            'talent' => $this->app()->make(GetTalentDraftWikiInterface::class)->process(new GetTalentDraftWikiInput($wikiIdentifier, $principalIdentifier)),
            default => throw new InvalidArgumentException('Unsupported resource type.'),
        };
    }
}
