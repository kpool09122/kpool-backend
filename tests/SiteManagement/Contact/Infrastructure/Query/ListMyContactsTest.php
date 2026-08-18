<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts\ListMyContactsInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts\ListMyContactsInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts\ListMyContactsOutput;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Infrastructure\Query\ListMyContacts;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListMyContactsTest extends TestCase
{
    public function test__construct(): void
    {
        $this->assertInstanceOf(ListMyContacts::class, $this->app->make(ListMyContactsInterface::class));
    }

    #[Group('useDb')]
    public function testProcessReturnsOnlyAuthenticatedIdentityContactsNewestFirst(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $otherIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $olderContactIdentifier = StrTestHelper::generateUuid();
        $newerContactIdentifier = StrTestHelper::generateUuid();

        $this->insertContact(
            id: $olderContactIdentifier,
            identityIdentifier: (string) $identityIdentifier,
            category: Category::SUGGESTIONS,
            name: '古い問い合わせ',
            email: 'older@example.com',
            content: '古い内容',
            createdAt: '2026-08-15 10:00:00',
        );
        $this->insertContact(
            id: $newerContactIdentifier,
            identityIdentifier: (string) $identityIdentifier,
            category: Category::ISSUES,
            name: '新しい問い合わせ',
            email: 'newer@example.com',
            content: '新しい内容',
            createdAt: '2026-08-16 10:00:00',
        );
        $this->insertContact(
            id: StrTestHelper::generateUuid(),
            identityIdentifier: (string) $otherIdentityIdentifier,
            category: Category::OTHERS,
            name: '他ユーザー',
            email: 'other@example.com',
            content: '他ユーザーの内容',
            createdAt: '2026-08-17 10:00:00',
        );
        $this->insertContact(
            id: StrTestHelper::generateUuid(),
            identityIdentifier: null,
            category: Category::OTHERS,
            name: '匿名ユーザー',
            email: 'anonymous@example.com',
            content: '匿名の内容',
            createdAt: '2026-08-17 11:00:00',
        );

        $output = new ListMyContactsOutput();
        $this->app->make(ListMyContactsInterface::class)->process(new ListMyContactsInput($identityIdentifier), $output);

        $this->assertSame([
            [
                'contactIdentifier' => $newerContactIdentifier,
                'identityIdentifier' => (string) $identityIdentifier,
                'category' => Category::ISSUES->value,
                'name' => '新しい問い合わせ',
                'email' => 'newer@example.com',
                'content' => '新しい内容',
            ],
            [
                'contactIdentifier' => $olderContactIdentifier,
                'identityIdentifier' => (string) $identityIdentifier,
                'category' => Category::SUGGESTIONS->value,
                'name' => '古い問い合わせ',
                'email' => 'older@example.com',
                'content' => '古い内容',
            ],
        ], $output->toArray());
    }

    #[Group('useDb')]
    public function testProcessReturnsEmptyArrayWhenIdentityHasNoContacts(): void
    {
        $output = new ListMyContactsOutput();

        $this->app->make(ListMyContactsInterface::class)->process(
            new ListMyContactsInput(new IdentityIdentifier(StrTestHelper::generateUuid())),
            $output,
        );

        $this->assertSame([], $output->toArray());
    }

    private function insertContact(
        string $id,
        ?string $identityIdentifier,
        Category $category,
        string $name,
        string $email,
        string $content,
        string $createdAt,
    ): void {
        $encryptionService = $this->app->make(EncryptionServiceInterface::class);

        DB::table('contacts')->insert([
            'id' => $id,
            'identity_identifier' => $identityIdentifier,
            'category' => $category->value,
            'name' => $name,
            'email' => $encryptionService->encrypt($email),
            'content' => $content,
            'language' => 'ja',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
