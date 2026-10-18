<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Infrastructure\Query;

use Application\Models\SiteManagement\Policy as PolicyModel;
use Database\Seeders\SiteManagementAuthorizationSeeder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group as PHPUnitGroup;
use Source\SiteManagement\Announcement\Application\UseCase\Query\GetDraftAnnouncement\GetDraftAnnouncementInput;
use Source\SiteManagement\Announcement\Application\UseCase\Query\GetDraftAnnouncement\GetDraftAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements\ListDraftAnnouncementsInput;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements\ListDraftAnnouncementsInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements\ListDraftAnnouncementsOutput;
use Source\SiteManagement\Announcement\Domain\Repository\DraftAnnouncementRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Tests\Helper\SiteManagementPrincipalGroupsFixture;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class DraftAnnouncementAuthorizationTest extends AnnouncementTestCase
{
    #[PHPUnitGroup('useDb')]
    #[DataProvider('principals')]
    public function testOnlyOperatorMayUseBothQueries(string $role, bool $allowed): void
    {
        $fixture = new SiteManagementPrincipalGroupsFixture();
        $fixture->group($fixture->account, [SiteManagementAuthorizationSeeder::GENERAL_ROLE], [$fixture->unassigned]);
        $principal = match ($role) {
            'operator', 'denied operator' => $fixture->administrator->principalIdentifier(),
            'administrator' => $fixture->otherAdministrator->principalIdentifier(),
            'general' => $fixture->unassigned->principalIdentifier(),
            default => new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000009'),
        };
        if ($role === 'denied operator') {
            $policy = PolicyModel::query()->findOrFail(SiteManagementAuthorizationSeeder::OPERATOR_ROLE);
            $policy->statements = [...$policy->statements, ['effect' => 'deny', 'actions' => ['announcement:view-draft'], 'resource_types' => ['announcement'], 'condition' => null]];
            $policy->save();
        }
        $draft = $this->draft();
        $this->app()->make(DraftAnnouncementRepositoryInterface::class)->save($draft);
        $output = new ListDraftAnnouncementsOutput();

        try {
            $this->app()->make(ListDraftAnnouncementsInterface::class)->process(new ListDraftAnnouncementsInput($principal), $output);
            $this->assertTrue($allowed);
            $this->assertSame(1, $output->toArray()['total']);
        } catch (UnauthorizedException) {
            $this->assertFalse($allowed);
        }

        try {
            $result = $this->app()->make(GetDraftAnnouncementInterface::class)->process(new GetDraftAnnouncementInput($principal, $draft->announcementIdentifier()));
            $this->assertTrue($allowed);
            $this->assertSame((string) $draft->announcementIdentifier(), $result->toArray()['announcementIdentifier']);
        } catch (UnauthorizedException) {
            $this->assertFalse($allowed);
        }
    }

    /** @return iterable<string, array{string, bool}> */
    public static function principals(): iterable
    {
        yield 'operator' => ['operator', true];
        yield 'general' => ['general', false];
        yield 'administrator without operator' => ['administrator', false];
        yield 'missing principal' => ['missing', false];
        yield 'explicit deny overrides operator' => ['denied operator', false];
    }
}
