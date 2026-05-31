<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedProfiles\ListRelatedProfilesInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedProfiles\ListRelatedProfilesInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedProfiles\ListRelatedProfilesOutput;
use Tests\Helper\CreateImage;
use Tests\Helper\CreateWiki;
use Tests\TestCase;

class ListRelatedProfilesTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsTalentsRelatedToGroup(): void
    {
        $this->createGroup('01965bb2-bcc9-7c6f-8b90-89f7f217f101', 'gr-twice', 'TWICE', 'twice');
        $this->createTalent('01965bb2-bcc9-7c6f-8b90-89f7f217f102', 'tl-momo', 'Momo', 'momo', [
            '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
        ]);
        $this->createTalent('01965bb2-bcc9-7c6f-8b90-89f7f217f103', 'tl-sana', 'Sana', 'sana', [
            '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
        ]);
        $this->createTalent('01965bb2-bcc9-7c6f-8b90-89f7f217f104', 'tl-unrelated', 'Unrelated', 'unrelated', []);

        $payload = $this->process(new ListRelatedProfilesInput(
            new Slug('gr-twice'),
            Language::KOREAN,
            ResourceType::TALENT,
        ))->toArray();

        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f102',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f103',
        ], array_column($payload['profiles'], 'wikiIdentifier'));
        $this->assertSame('tl-momo', $payload['profiles'][0]['slug']);
        $this->assertSame('ko', $payload['profiles'][0]['language']);
        $this->assertSame('talent', $payload['profiles'][0]['resourceType']);
        $this->assertSame('Momo', $payload['profiles'][0]['name']);
        $this->assertSame('momo', $payload['profiles'][0]['normalizedName']);
    }

    #[Group('useDb')]
    public function testProcessReturnsGroupsRelatedToTalent(): void
    {
        $this->createGroup('01965bb2-bcc9-7c6f-8b90-89f7f217f201', 'gr-twice', 'TWICE', 'twice');
        $this->createGroup('01965bb2-bcc9-7c6f-8b90-89f7f217f202', 'gr-misamo', 'MISAMO', 'misamo');
        $this->createTalent('01965bb2-bcc9-7c6f-8b90-89f7f217f203', 'tl-momo', 'Momo', 'momo', [
            '01965bb2-bcc9-7c6f-8b90-89f7f217f201',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f202',
        ]);

        $payload = $this->process(new ListRelatedProfilesInput(
            new Slug('tl-momo'),
            Language::KOREAN,
            ResourceType::GROUP,
        ))->toArray();

        $this->assertSame(['gr-misamo', 'gr-twice'], array_column($payload['profiles'], 'slug'));
    }

    #[Group('useDb')]
    public function testProcessReturnsSongsRelatedToTalent(): void
    {
        $this->createTalent('01965bb2-bcc9-7c6f-8b90-89f7f217f301', 'tl-nayeon', 'Nayeon', 'nayeon', []);
        $this->createSong('01965bb2-bcc9-7c6f-8b90-89f7f217f302', 'sg-pop', 'POP!', 'pop', [], [
            '01965bb2-bcc9-7c6f-8b90-89f7f217f301',
        ]);
        $this->createSong('01965bb2-bcc9-7c6f-8b90-89f7f217f303', 'sg-unrelated', 'Unrelated', 'unrelated', [], []);

        $payload = $this->process(new ListRelatedProfilesInput(
            new Slug('tl-nayeon'),
            Language::KOREAN,
            ResourceType::SONG,
        ))->toArray();

        $this->assertSame(['sg-pop'], array_column($payload['profiles'], 'slug'));
    }

    #[Group('useDb')]
    public function testProcessReturnsAgencyRelatedToGroup(): void
    {
        $this->createAgency('01965bb2-bcc9-7c6f-8b90-89f7f217f401', 'ag-jyp', 'JYP Entertainment', 'jyp entertainment');
        $this->createGroup(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f402',
            'gr-twice',
            'TWICE',
            'twice',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f401',
        );

        $payload = $this->process(new ListRelatedProfilesInput(
            new Slug('gr-twice'),
            Language::KOREAN,
            ResourceType::AGENCY,
        ))->toArray();

        $this->assertSame(['ag-jyp'], array_column($payload['profiles'], 'slug'));
    }

    #[Group('useDb')]
    public function testProcessReturnsGroupsRelatedToAgency(): void
    {
        $this->createAgency('01965bb2-bcc9-7c6f-8b90-89f7f217f501', 'ag-jyp', 'JYP Entertainment', 'jyp entertainment');
        $this->createGroup(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f502',
            'gr-twice',
            'TWICE',
            'twice',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f501',
        );
        $this->createGroup('01965bb2-bcc9-7c6f-8b90-89f7f217f503', 'gr-unrelated', 'Unrelated', 'unrelated');

        $payload = $this->process(new ListRelatedProfilesInput(
            new Slug('ag-jyp'),
            Language::KOREAN,
            ResourceType::GROUP,
        ))->toArray();

        $this->assertSame(['gr-twice'], array_column($payload['profiles'], 'slug'));
    }

    #[Group('useDb')]
    public function testProcessReturnsImageFields(): void
    {
        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f601', [
            'image_path' => '/images/test/momo.jpg',
            'alt_text' => 'Momo profile image',
        ]);
        $this->createGroup('01965bb2-bcc9-7c6f-8b90-89f7f217f602', 'gr-twice', 'TWICE', 'twice');
        $this->createTalent('01965bb2-bcc9-7c6f-8b90-89f7f217f603', 'tl-momo', 'Momo', 'momo', [
            '01965bb2-bcc9-7c6f-8b90-89f7f217f602',
        ], imageIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f601');

        $payload = $this->process(new ListRelatedProfilesInput(
            new Slug('gr-twice'),
            Language::KOREAN,
            ResourceType::TALENT,
        ))->toArray();

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f601', $payload['profiles'][0]['imageIdentifier']);
        $this->assertSame('http://127.0.0.1:8080/images/test/momo.jpg', $payload['profiles'][0]['imageUrl']);
        $this->assertSame('Momo profile image', $payload['profiles'][0]['imageAltText']);
    }

    #[Group('useDb')]
    public function testProcessReturnsEmptyWhenNoRelationExists(): void
    {
        $this->createGroup('01965bb2-bcc9-7c6f-8b90-89f7f217f701', 'gr-twice', 'TWICE', 'twice');

        $payload = $this->process(new ListRelatedProfilesInput(
            new Slug('gr-twice'),
            Language::KOREAN,
            ResourceType::SONG,
        ))->toArray();

        $this->assertSame([], $payload['profiles']);
    }

    #[Group('useDb')]
    public function testProcessThrowsWikiNotFoundExceptionWhenSourceWikiDoesNotExist(): void
    {
        $this->expectException(WikiNotFoundException::class);

        $this->process(new ListRelatedProfilesInput(
            new Slug('gr-missing'),
            Language::KOREAN,
            ResourceType::TALENT,
        ));
    }

    #[Group('useDb')]
    public function testProcessThrowsInvalidArgumentExceptionWhenTargetTypeEqualsSourceType(): void
    {
        $this->createGroup('01965bb2-bcc9-7c6f-8b90-89f7f217f801', 'gr-twice', 'TWICE', 'twice');

        $this->expectException(InvalidArgumentException::class);

        $this->process(new ListRelatedProfilesInput(
            new Slug('gr-twice'),
            Language::KOREAN,
            ResourceType::GROUP,
        ));
    }

    private function process(ListRelatedProfilesInput $input): ListRelatedProfilesOutput
    {
        $output = new ListRelatedProfilesOutput();
        $this->app->make(ListRelatedProfilesInterface::class)->process($input, $output);

        return $output;
    }

    private function createAgency(string $wikiId, string $slug, string $name, string $normalizedName): void
    {
        CreateWiki::create($wikiId, 'agency', [
            'slug' => $slug,
            'language' => 'ko',
            'published_at' => '2026-04-01 00:00:00',
        ], [
            'name' => $name,
            'normalized_name' => $normalizedName,
        ]);
    }

    private function createGroup(
        string $wikiId,
        string $slug,
        string $name,
        string $normalizedName,
        ?string $agencyIdentifier = null,
    ): void {
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

    /**
     * @param list<string> $groupIdentifiers
     */
    private function createTalent(
        string $wikiId,
        string $slug,
        string $name,
        string $normalizedName,
        array $groupIdentifiers,
        ?string $agencyIdentifier = null,
        ?string $imageIdentifier = null,
    ): void {
        CreateWiki::create($wikiId, 'talent', [
            'slug' => $slug,
            'language' => 'ko',
            'image_identifier' => $imageIdentifier,
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
    private function createSong(
        string $wikiId,
        string $slug,
        string $name,
        string $normalizedName,
        array $groupIdentifiers,
        array $talentIdentifiers,
        ?string $agencyIdentifier = null,
    ): void {
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

        DB::table('wikis')->where('id', $wikiId)->update(['updated_at' => '2026-05-01 00:00:00']);
    }
}
