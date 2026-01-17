<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\ApproveAgency;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Application\UseCase\Command\ApproveAgency\ApproveAgencyOutput;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveAgencyOutputTest extends TestCase
{
    /**
     * 正常系: DraftAgencyがセットされている場合、toArrayが正しい値を返すこと.
     *
     * @return void
     */
    public function testToArrayWithDraftAgency(): void
    {
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));

        $draftAgency = new DraftAgency(
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::JAPANESE,
            new AgencyName('JYPエンターテイメント'),
            'JYPエンターテイメント',
            new CEO('J.Y. Park'),
            'j.y. park',
            $foundedIn,
            new Description('韓国の大型総合エンターテイメント企業です。'),
            ApprovalStatus::Approved,
        );

        $output = new ApproveAgencyOutput();
        $output->setDraftAgency($draftAgency);

        $result = $output->toArray();

        $this->assertSame('ja', $result['language']);
        $this->assertSame('JYPエンターテイメント', $result['name']);
        $this->assertSame('J.Y. Park', $result['CEO']);
        $this->assertSame((string) $foundedIn, $result['foundedIn']);
        $this->assertSame('韓国の大型総合エンターテイメント企業です。', $result['description']);
        $this->assertSame('approved', $result['status']);
    }

    /**
     * 正常系: DraftAgencyがセットされていない場合、toArrayが全てnullの配列を返すこと.
     *
     * @return void
     */
    public function testToArrayWithoutDraftAgency(): void
    {
        $output = new ApproveAgencyOutput();

        $result = $output->toArray();

        $this->assertSame([
            'language' => null,
            'name' => null,
            'CEO' => null,
            'foundedIn' => null,
            'description' => null,
            'status' => null,
        ], $result);
    }

    /**
     * 正常系: DraftAgencyのfoundedInがnullの場合、toArrayでfoundedInがnullになること.
     *
     * @return void
     */
    public function testToArrayWithNullFoundedIn(): void
    {
        $draftAgency = new DraftAgency(
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new AgencyName('HYBE'),
            'HYBE',
            new CEO('パク・ジウォン'),
            'パク・ジウォン',
            null,
            new Description('エンターテインメントライフスタイルプラットフォーム企業です。'),
            ApprovalStatus::UnderReview,
        );

        $output = new ApproveAgencyOutput();
        $output->setDraftAgency($draftAgency);

        $result = $output->toArray();

        $this->assertSame('ko', $result['language']);
        $this->assertSame('HYBE', $result['name']);
        $this->assertSame('パク・ジウォン', $result['CEO']);
        $this->assertNull($result['foundedIn']);
        $this->assertSame('エンターテインメントライフスタイルプラットフォーム企業です。', $result['description']);
        $this->assertSame('under_review', $result['status']);
    }
}
