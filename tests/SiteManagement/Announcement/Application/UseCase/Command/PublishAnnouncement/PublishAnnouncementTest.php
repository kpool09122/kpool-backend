<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement;

use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group as PHPUnitGroup;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement\PublishAnnouncementInput;
use Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement\PublishAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement\PublishAnnouncementOutput;
use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\Repository\DraftAnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementContents;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\LocalizedAnnouncementContent;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class PublishAnnouncementTest extends AnnouncementTestCase
{
    #[PHPUnitGroup('useDb')]
    public function testPublishesAllContentsTogetherAndUpdatesSameIdentity(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_PUBLISH);
        $draft = $this->draft();
        $draft->setLocalizedContent($this->localized(Language::KOREAN));
        $draft->setLocalizedContent($this->localized(Language::ENGLISH));
        $draftRepository = $this->app()->make(DraftAnnouncementRepositoryInterface::class);
        $publishedRepository = $this->app()->make(AnnouncementRepositoryInterface::class);
        $draftRepository->save($draft);
        $service = $this->app()->make(PublishAnnouncementInterface::class);
        $output = new PublishAnnouncementOutput();
        $input = new PublishAnnouncementInput($principal, $this->draft()->announcementIdentifier());
        $service->process($input, $output);
        $this->assertCount(1, $output->announcements());
        $this->assertNull($draftRepository->findById($draft->announcementIdentifier()));
        $this->assertEquals($draft->contents()->toArray(), $publishedRepository->findById($draft->announcementIdentifier())?->contents()->toArray());
        $draft->setLocalizedContent(new LocalizedAnnouncementContent(Language::ENGLISH, new Title('updated English title'), new Content('updated English body')));
        $draftRepository->save($draft);
        $service->process($input, new PublishAnnouncementOutput());
        $this->assertSame('updated English title', (string) $publishedRepository->findById($draft->announcementIdentifier())?->localizedContent(Language::ENGLISH)?->title);
        $this->assertDatabaseCount('announcements', 1);
        $this->assertCount(3, $publishedRepository->findById($draft->announcementIdentifier())?->contents()->toArray() ?? []);
    }

    #[PHPUnitGroup('useDb')]
    #[DataProvider('incompleteContents')]
    public function testRejectsIncompleteContentWithoutChangingDraftOrPublication(Language $language): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_PUBLISH);
        $original = $this->draft();
        foreach (Language::cases() as $supported) {
            $original->setLocalizedContent($this->localized($supported));
        }
        $publishedRepository = $this->app()->make(AnnouncementRepositoryInterface::class);
        $publishedRepository->save(new Announcement($original->announcementIdentifier(), $original->sourceLanguage(), $original->category(), $original->contents(), $this->publishedDate()));
        $contents = new AnnouncementContents();
        foreach (Language::cases() as $supported) {
            if ($supported === $language) {
                continue;
            }
            $contents = $contents->with(new LocalizedAnnouncementContent(
                $supported,
                new Title($supported->value . ' title'),
                new Content($supported->value . ' body'),
            ));
        }
        $sourceLanguage = $language === Language::JAPANESE ? Language::KOREAN : Language::JAPANESE;
        $draft = new DraftAnnouncement($original->announcementIdentifier(), $sourceLanguage, $original->category(), $contents);
        $draftRepository = $this->app()->make(DraftAnnouncementRepositoryInterface::class);
        $draftRepository->save($draft);
        $output = new PublishAnnouncementOutput();

        try {
            $this->app()->make(PublishAnnouncementInterface::class)->process(new PublishAnnouncementInput($principal, $draft->announcementIdentifier()), $output);
            $this->fail('Incomplete content must prevent publication.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('All announcement languages require a non-empty title and content.', $exception->getMessage());
        }
        $this->assertEquals($contents->toArray(), $draftRepository->findById($draft->announcementIdentifier())?->contents()->toArray());
        $this->assertEquals($original->contents()->toArray(), $publishedRepository->findById($draft->announcementIdentifier())?->contents()->toArray());
        $this->assertSame([], $output->announcements());
    }

    /** @return iterable<string, array{Language}> */
    public static function incompleteContents(): iterable
    {
        foreach (Language::cases() as $language) {
            yield $language->value => [$language];
        }
    }

    #[PHPUnitGroup('useDb')]
    public function testMissingDraft(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_PUBLISH);
        $this->expectException(AnnouncementNotFoundException::class);
        $this->app()->make(PublishAnnouncementInterface::class)->process(new PublishAnnouncementInput($principal, $this->draft()->announcementIdentifier()), new PublishAnnouncementOutput());
    }

    public function testDeniesWithoutPermission(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_PUBLISH, false);
        $repository = Mockery::mock(DraftAnnouncementRepositoryInterface::class);
        $repository->shouldNotReceive('findById');
        $repository->shouldNotReceive('save');
        $repository->shouldNotReceive('delete');
        $this->app()->instance(DraftAnnouncementRepositoryInterface::class, $repository);
        $this->expectException(UnauthorizedException::class);
        $this->app()->make(PublishAnnouncementInterface::class)->process(new PublishAnnouncementInput($principal, $this->draft()->announcementIdentifier()), new PublishAnnouncementOutput());
    }

    public function testDeniesMissingPrincipal(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_PUBLISH, true, false);
        $this->expectException(UnauthorizedException::class);
        $this->app()->make(PublishAnnouncementInterface::class)->process(new PublishAnnouncementInput($principal, $this->draft()->announcementIdentifier()), new PublishAnnouncementOutput());
    }
}
