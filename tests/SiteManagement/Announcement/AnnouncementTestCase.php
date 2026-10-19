<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement;

use DateTimeImmutable;
use Mockery;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementContents;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\LocalizedAnnouncementContent;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Tests\TestCase;

abstract class AnnouncementTestCase extends TestCase
{
    protected function draft(): DraftAnnouncement
    {
        return new DraftAnnouncement(
            new AnnouncementIdentifier('019c9b4c-0000-7000-8000-000000000001'),
            Language::JAPANESE,
            Category::NEWS,
            new AnnouncementContents($this->localized(Language::JAPANESE)),
        );
    }

    protected function publishedDate(): PublishedDate
    {
        return new PublishedDate(new DateTimeImmutable('2026-10-01'));
    }

    protected function localized(Language $language): LocalizedAnnouncementContent
    {
        return new LocalizedAnnouncementContent($language, new Title($language->value . ' title'), new Content($language->value . ' body'));
    }

    protected function authorize(Action $action, bool $allowed = true, bool $exists = true): PrincipalIdentifier
    {
        $identifier = new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000009');
        $principal = new Principal($identifier, new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000008'), new AccountIdentifier('019c9b4c-0000-7000-8000-000000000007'));
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->with($identifier)->andReturn($exists ? $principal : null);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->withArgs(static fn (Principal $subject, Action $requested, Resource $resource): bool => $requested === $action && $resource->type() === ResourceType::ANNOUNCEMENT)->andReturn($allowed);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);

        return $identifier;
    }
}
