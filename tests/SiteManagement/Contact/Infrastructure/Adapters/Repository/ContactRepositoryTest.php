<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Adapters\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
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
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $contact = new Contact(
            new ContactIdentifier(StrTestHelper::generateUuid()),
            $identityIdentifier,
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
        $this->assertSame((string)$identityIdentifier, $record->identity_identifier);
        $this->assertSame((string)$contact->name(), $record->name);
        // 保存時は暗号化されていること（平文と一致しない）
        $this->assertNotSame((string)$contact->email(), $record->email);
        $this->assertNotEmpty($record->email);
        // 復号すると登録したメールアドレスと一致すること
        $encryptionService = $this->app->make(EncryptionServiceInterface::class);
        $this->assertSame((string)$contact->email(), $encryptionService->decrypt($record->email));
        $this->assertSame((string)$contact->content(), $record->content);
    }

    /**
     * 正常系：ID指定で問い合わせを取得できること（メールアドレスは復号されること）
     *
     * @throws BindingResolutionException
     * @return void
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $encryptionService = $this->app->make(EncryptionServiceInterface::class);

        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $email = new Email('john.doe@example.com');
        $createdAt = new DateTimeImmutable('2026-01-01 00:00:00');

        DB::table('contacts')->insert([
            'id' => (string)$contactIdentifier,
            'category' => Category::SUGGESTIONS->value,
            'name' => 'お名前',
            'email' => $encryptionService->encrypt((string)$email),
            'content' => 'お問い合わせ内容',
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $createdAt->format('Y-m-d H:i:s'),
        ]);

        $repository = $this->app->make(ContactRepositoryInterface::class);
        $contact = $repository->findById($contactIdentifier);

        $this->assertNotNull($contact);
        $this->assertSame((string)$contactIdentifier, (string)$contact->contactIdentifier());
        $this->assertSame(Category::SUGGESTIONS->value, $contact->category()->value);
        $this->assertSame('お名前', (string)$contact->name());
        $this->assertSame((string)$email, (string)$contact->email());
        $this->assertSame('お問い合わせ内容', (string)$contact->content());
    }

    /**
     * 正常系：存在しないIDの場合は null を返すこと
     *
     * @throws BindingResolutionException
     * @return void
     */
    #[Group('useDb')]
    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $repository = $this->app->make(ContactRepositoryInterface::class);
        $contact = $repository->findById(new ContactIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($contact);
    }
}
