<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\Slug;

class SlugTest extends TestCase
{
    public function testConstruct(): void
    {
        $slug = new Slug('gr-valid-slug');

        $this->assertSame('gr-valid-slug', (string) $slug);
    }

    public function testMinLength(): void
    {
        $slug = new Slug('gr-a');

        $this->assertSame('gr-a', (string) $slug);
    }

    public function testMaxLength(): void
    {
        $value = 'gr-' . str_repeat('a', Slug::MAX_LENGTH - 3);
        $slug = new Slug($value);

        $this->assertSame($value, (string) $slug);
    }

    public function testWithNumbersAndHyphens(): void
    {
        $slug = new Slug('sg-slug-123-test');

        $this->assertSame('sg-slug-123-test', (string) $slug);
    }

    public function testEmptySlug(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot be empty');

        new Slug('');
    }

    public function testBelowMinLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug must be at least ' . Slug::MIN_LENGTH . ' characters');

        new Slug('ab');
    }

    public function testExceedsMaxLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot exceed ' . Slug::MAX_LENGTH . ' characters');

        new Slug('gr-' . str_repeat('a', Slug::MAX_LENGTH - 2));
    }

    #[DataProvider('invalidFormatProvider')]
    public function testInvalidFormat(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug must contain only lowercase letters, numbers, and hyphens');

        new Slug($value);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidFormatProvider(): array
    {
        return [
            'uppercase' => ['Gr-invalid-slug'],
            'starts with hyphen' => ['-gr-invalid-slug'],
            'ends with hyphen' => ['gr-invalid-slug-'],
            'consecutive hyphens' => ['gr-invalid--slug'],
            'space' => ['gr-invalid slug'],
            'underscore' => ['gr-invalid_slug'],
            'special character' => ['gr-invalid@slug'],
        ];
    }

    #[DataProvider('invalidPrefixProvider')]
    public function testInvalidPrefix(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug must start with a valid resource prefix: ag-, gr-, sg-, or tl-.');

        new Slug($value);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidPrefixProvider(): array
    {
        return [
            'missing prefix' => ['valid-slug'],
            'unsupported prefix' => ['xx-valid-slug'],
        ];
    }

    public function testReservedWordAsPartOfSlug(): void
    {
        $slug = new Slug('gr-admin-page');
        $slug2 = new Slug('sg-my-api-test');

        $this->assertSame('gr-admin-page', (string) $slug);
        $this->assertSame('sg-my-api-test', (string) $slug2);
    }
}
