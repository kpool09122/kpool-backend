<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Infrastructure\Service;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Source\Account\AccountVerification\Application\Service\DocumentStorageServiceInterface;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentPath;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Source\Account\AccountVerification\Infrastructure\Service\DocumentStorageService;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DocumentStorageServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('verification-documents');
    }

    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $service = $this->app->make(DocumentStorageServiceInterface::class);
        $this->assertInstanceOf(DocumentStorageService::class, $service);
    }

    /**
     * 正常系: ファイルを保存してDocumentPathが返却されること
     */
    public function testStore(): void
    {
        $service = new DocumentStorageService();
        $verificationId = new VerificationIdentifier(StrTestHelper::generateUuid());
        $fileName = 'test-document.jpg';
        $contents = 'test file contents';

        $path = $service->store($verificationId, $fileName, $contents);

        $this->assertStringContainsString('verifications/', (string) $path);
        $this->assertStringContainsString((string) $verificationId, (string) $path);
        $this->assertStringContainsString('test-document.jpg', (string) $path);

        Storage::disk('verification-documents')->assertExists((string) $path);
    }

    /**
     * 正常系: 保存したファイルの内容を取得できること
     */
    public function testGet(): void
    {
        $service = new DocumentStorageService();
        $verificationId = new VerificationIdentifier(StrTestHelper::generateUuid());
        $fileName = 'test-document.jpg';
        $contents = 'test file contents';

        $path = $service->store($verificationId, $fileName, $contents);
        $retrievedContents = $service->get($path);

        $this->assertSame($contents, $retrievedContents);
    }

    /**
     * 正常系: 存在しないファイルの取得はnullが返却されること
     */
    public function testGetReturnsNullWhenNotExists(): void
    {
        $service = new DocumentStorageService();
        $path = new DocumentPath('verifications/non-existent/file.jpg');

        $result = $service->get($path);

        $this->assertNull($result);
    }

    /**
     * 正常系: ファイルを削除できること
     */
    public function testDelete(): void
    {
        $service = new DocumentStorageService();
        $verificationId = new VerificationIdentifier(StrTestHelper::generateUuid());
        $fileName = 'test-document.jpg';
        $contents = 'test file contents';

        $path = $service->store($verificationId, $fileName, $contents);
        Storage::disk('verification-documents')->assertExists((string) $path);

        $result = $service->delete($path);

        $this->assertTrue($result);
        Storage::disk('verification-documents')->assertMissing((string) $path);
    }

    /**
     * 正常系: VerificationIdに紐づく全ファイルを削除できること
     */
    public function testDeleteByVerificationId(): void
    {
        $service = new DocumentStorageService();
        $verificationId = new VerificationIdentifier(StrTestHelper::generateUuid());

        // 複数のファイルを保存
        $path1 = $service->store($verificationId, 'document1.jpg', 'contents1');
        $path2 = $service->store($verificationId, 'document2.jpg', 'contents2');

        Storage::disk('verification-documents')->assertExists((string) $path1);
        Storage::disk('verification-documents')->assertExists((string) $path2);

        $result = $service->deleteByVerificationId($verificationId);

        $this->assertTrue($result);
        Storage::disk('verification-documents')->assertMissing((string) $path1);
        Storage::disk('verification-documents')->assertMissing((string) $path2);
    }

    /**
     * 正常系: ファイルの存在確認ができること
     */
    public function testExists(): void
    {
        $service = new DocumentStorageService();
        $verificationId = new VerificationIdentifier(StrTestHelper::generateUuid());
        $fileName = 'test-document.jpg';
        $contents = 'test file contents';

        $path = $service->store($verificationId, $fileName, $contents);

        $this->assertTrue($service->exists($path));
    }

    /**
     * 正常系: 存在しないファイルの存在確認はfalseが返却されること
     */
    public function testExistsReturnsFalseWhenNotExists(): void
    {
        $service = new DocumentStorageService();
        $path = new DocumentPath('verifications/non-existent/file.jpg');

        $this->assertFalse($service->exists($path));
    }

    /**
     * 正常系: 一時URLを取得できること
     */
    public function testGetTemporaryUrl(): void
    {
        $service = new DocumentStorageService();
        $verificationId = new VerificationIdentifier(StrTestHelper::generateUuid());
        $fileName = 'test-document.jpg';
        $contents = 'test file contents';

        $path = $service->store($verificationId, $fileName, $contents);
        $url = $service->getTemporaryUrl($path);

        // URLにパスが含まれていることを確認
        $this->assertStringContainsString((string) $path, $url);
    }

    /**
     * 正常系: temporaryUrlをサポートしないディスクではパスがそのまま返却されること
     */
    public function testGetTemporaryUrlReturnsPathWhenNotSupported(): void
    {
        $path = new DocumentPath('verifications/test-id/1234567890_test.jpg');

        $mockDisk = Mockery::mock(Filesystem::class);
        Storage::shouldReceive('disk')
            ->with('verification-documents')
            ->andReturn($mockDisk);

        $service = new DocumentStorageService();
        $url = $service->getTemporaryUrl($path);

        $this->assertSame((string) $path, $url);
    }

    /**
     * 正常系: 特殊文字を含むファイル名がサニタイズされること
     */
    public function testStoreWithSpecialCharactersInFileName(): void
    {
        $service = new DocumentStorageService();
        $verificationId = new VerificationIdentifier(StrTestHelper::generateUuid());
        $fileName = 'test<script>alert("xss")</script>.jpg';
        $contents = 'test file contents';

        $path = $service->store($verificationId, $fileName, $contents);

        $this->assertStringNotContainsString('<', (string) $path);
        $this->assertStringNotContainsString('>', (string) $path);
        $this->assertStringNotContainsString('"', (string) $path);
        Storage::disk('verification-documents')->assertExists((string) $path);
    }

    /**
     * 正常系: パスを含むファイル名からパス部分が除去されること
     */
    public function testStoreWithPathInFileName(): void
    {
        $service = new DocumentStorageService();
        $verificationId = new VerificationIdentifier(StrTestHelper::generateUuid());
        $fileName = '/etc/passwd/../../../malicious.jpg';
        $contents = 'test file contents';

        $path = $service->store($verificationId, $fileName, $contents);

        $this->assertStringNotContainsString('/etc/', (string) $path);
        $this->assertStringNotContainsString('..', (string) $path);
        $this->assertStringContainsString('malicious.jpg', (string) $path);
        Storage::disk('verification-documents')->assertExists((string) $path);
    }

    /**
     * 正常系: 長いファイル名が切り詰められること
     */
    public function testStoreWithLongFileName(): void
    {
        $service = new DocumentStorageService();
        $verificationId = new VerificationIdentifier(StrTestHelper::generateUuid());
        $longName = str_repeat('a', 300) . '.jpg';
        $contents = 'test file contents';

        $path = $service->store($verificationId, $longName, $contents);

        // パス全体の中のファイル名部分が200文字以下であることを確認
        $pathParts = explode('/', (string) $path);
        $storedFileName = end($pathParts);
        // タイムスタンプ部分を除いたファイル名部分
        $fileNamePart = preg_replace('/^\d+_/', '', $storedFileName);
        $this->assertLessThanOrEqual(200, strlen($fileNamePart));
        Storage::disk('verification-documents')->assertExists((string) $path);
    }

    /**
     * 正常系: 日本語ファイル名がサニタイズされること
     */
    public function testStoreWithJapaneseFileName(): void
    {
        $service = new DocumentStorageService();
        $verificationId = new VerificationIdentifier(StrTestHelper::generateUuid());
        $fileName = '本人確認書類.jpg';
        $contents = 'test file contents';

        $path = $service->store($verificationId, $fileName, $contents);

        // 日本語がアンダースコアに置換される
        $this->assertStringContainsString('.jpg', (string) $path);
        Storage::disk('verification-documents')->assertExists((string) $path);
    }

    /**
     * 正常系: 複数のファイルを同じVerificationIdで保存できること
     */
    public function testStoreMultipleFiles(): void
    {
        $service = new DocumentStorageService();
        $verificationId = new VerificationIdentifier(StrTestHelper::generateUuid());

        $path1 = $service->store($verificationId, 'document1.jpg', 'contents1');
        $path2 = $service->store($verificationId, 'document2.jpg', 'contents2');
        $path3 = $service->store($verificationId, 'document3.jpg', 'contents3');

        $this->assertNotSame((string) $path1, (string) $path2);
        $this->assertNotSame((string) $path2, (string) $path3);

        $this->assertTrue($service->exists($path1));
        $this->assertTrue($service->exists($path2));
        $this->assertTrue($service->exists($path3));
    }
}
