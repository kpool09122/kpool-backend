<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Adapters\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\Repository\ContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ContactRepositoryTest extends TestCase
{
    /**
     * 正常系：問い合わせを保存できること
     *
     * @throws BindingResolutionException
     * @return void
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $contact = new Contact(
            new ContactIdentifier(StrTestHelper::generateUuid()),
            Category::SUGGESTIONS,
            new ContactName('お名前'),
            new Email('john.doe@example.com'),
            new Content('お問い合わせ内容')
        );

        $repository = $this->app->make(ContactRepositoryInterface::class);
        $repository->save($contact);

        $record = DB::table('contacts')
            ->where('id', (string)$contact->contactIdentifier())
            ->first();

        $this->assertNotNull($record);
        $this->assertSame($contact->category()->value, (int)$record->category);
        $this->assertSame((string)$contact->name(), $record->name);
        // 保存時は暗号化されていること（平文と一致しない）
        $this->assertNotSame((string)$contact->email(), $record->email);
        $this->assertNotEmpty($record->email);
        // 復号すると登録したメールアドレスと一致すること
        $encryptionService = $this->app->make(EncryptionServiceInterface::class);
        $this->assertSame((string)$contact->email(), $encryptionService->decrypt($record->email));
        $this->assertSame((string)$contact->content(), $record->content);
    }
}
