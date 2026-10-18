<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement;

use Mockery;
use PHPUnit\Framework\Attributes\Group as PHPUnitGroup;
use RuntimeException;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\Service\TranslationServiceInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement\TranslateAnnouncementInput;
use Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement\TranslateAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement\TranslateAnnouncementOutput;
use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Domain\Repository\DraftAnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\LocalizedAnnouncementContent;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;
use UnexpectedValueException;

class TranslateAnnouncementTest extends AnnouncementTestCase
{
    #[PHPUnitGroup('useDb')]
    public function testFillsOnlyMissingLanguagesAndKeepsSameDraft(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_TRANSLATE);
        $draft = $this->draft();
        $draft->setLocalizedContent(new LocalizedAnnouncementContent(Language::ENGLISH, new Title('manual en'), new Content('manual body')));
        $repository = $this->app()->make(DraftAnnouncementRepositoryInterface::class);
        $repository->save($draft);
        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateAnnouncement')->withArgs(static fn (LocalizedAnnouncementContent $source, Language $language): bool => $source->language === Language::JAPANESE && $language === Language::KOREAN)->once()->andReturn($this->localized(Language::KOREAN));
        $this->app()->instance(TranslationServiceInterface::class, $translationService);
        $service = $this->app()->make(TranslateAnnouncementInterface::class);
        $output = new TranslateAnnouncementOutput();
        $input = new TranslateAnnouncementInput($principal, $this->draft()->announcementIdentifier());
        $service->process($input, $output);
        $service->process($input, new TranslateAnnouncementOutput());
        $loaded = $repository->findById($draft->announcementIdentifier());
        $this->assertSame('manual en', (string) $loaded?->localizedContent(Language::ENGLISH)?->title);
        $this->assertSame('ko title', (string) $loaded?->localizedContent(Language::KOREAN)?->title);
        $this->assertSame('ja title', (string) $loaded?->localizedContent(Language::JAPANESE)?->title);
        $this->assertCount(1, $output->announcements());
        $this->assertDatabaseCount('draft_announcements', 1);
        $this->assertDatabaseCount('announcements', 0);
    }

    #[PHPUnitGroup('useDb')]
    public function testTranslationFailureDoesNotPersistPartialContents(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_TRANSLATE);
        $draft = $this->draft();
        $repository = $this->app()->make(DraftAnnouncementRepositoryInterface::class);
        $repository->save($draft);
        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateAnnouncement')->withArgs(static fn (LocalizedAnnouncementContent $source, Language $language): bool => $language === Language::KOREAN)->once()->andReturn($this->localized(Language::KOREAN));
        $translationService->shouldReceive('translateAnnouncement')->withArgs(static fn (LocalizedAnnouncementContent $source, Language $language): bool => $language === Language::ENGLISH)->once()->andThrow(new RuntimeException('translation failed'));
        $this->app()->instance(TranslationServiceInterface::class, $translationService);

        try {
            $this->app()->make(TranslateAnnouncementInterface::class)->process(new TranslateAnnouncementInput($principal, $this->draft()->announcementIdentifier()), new TranslateAnnouncementOutput());
            $this->fail('Expected translation failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('translation failed', $exception->getMessage());
        }
        $this->assertSame($draft->contents()->toArray(), $repository->findById($draft->announcementIdentifier())?->contents()->toArray());
    }

    #[PHPUnitGroup('useDb')]
    public function testRejectsWrongTranslationLanguage(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_TRANSLATE);
        $this->app()->make(DraftAnnouncementRepositoryInterface::class)->save($this->draft());
        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateAnnouncement')->andReturn($this->localized(Language::ENGLISH));
        $this->app()->instance(TranslationServiceInterface::class, $translationService);
        $this->expectException(UnexpectedValueException::class);
        $this->app()->make(TranslateAnnouncementInterface::class)->process(new TranslateAnnouncementInput($principal, $this->draft()->announcementIdentifier()), new TranslateAnnouncementOutput());
    }

    #[PHPUnitGroup('useDb')]
    public function testMissingDraft(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_TRANSLATE);
        $this->app()->instance(TranslationServiceInterface::class, Mockery::mock(TranslationServiceInterface::class));
        $this->expectException(AnnouncementNotFoundException::class);
        $output = new TranslateAnnouncementOutput();
        $this->app()->make(TranslateAnnouncementInterface::class)->process(new TranslateAnnouncementInput($principal, $this->draft()->announcementIdentifier()), $output);
    }

    public function testDeniesWithoutPermission(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_TRANSLATE, false);
        $repository = Mockery::mock(DraftAnnouncementRepositoryInterface::class);
        $repository->shouldNotReceive('findById');
        $repository->shouldNotReceive('save');
        $repository->shouldNotReceive('delete');
        $this->app()->instance(DraftAnnouncementRepositoryInterface::class, $repository);
        $this->app()->instance(TranslationServiceInterface::class, Mockery::mock(TranslationServiceInterface::class));
        $this->expectException(UnauthorizedException::class);
        $this->app()->make(TranslateAnnouncementInterface::class)->process(new TranslateAnnouncementInput($principal, $this->draft()->announcementIdentifier()), new TranslateAnnouncementOutput());
    }

    public function testDeniesMissingPrincipal(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_TRANSLATE, true, false);
        $this->app()->instance(TranslationServiceInterface::class, Mockery::mock(TranslationServiceInterface::class));
        $this->expectException(UnauthorizedException::class);
        $this->app()->make(TranslateAnnouncementInterface::class)->process(new TranslateAnnouncementInput($principal, $this->draft()->announcementIdentifier()), new TranslateAnnouncementOutput());
    }
}
