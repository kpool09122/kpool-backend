<?php

declare(strict_types=1);

namespace Tests\Http\Action\SiteManagement\Contact\Query\ListContacts;

use Application\Http\Action\SiteManagement\Contact\Query\ListContacts\ListContactsRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ListContactsRequestTest extends TestCase
{
    /** @return array<string, array{string, bool}> */
    public static function validHasReplyProvider(): array
    {
        return [
            'sent' => ['1', true],
            'not sent' => ['0', false],
        ];
    }

    #[DataProvider('validHasReplyProvider')]
    public function testHasReplyAcceptsNumericQueryValues(string $value, bool $expected): void
    {
        $request = ListContactsRequest::create('/api/site-management/contacts?hasReply='.$value);

        $this->assertTrue(Validator::make($request->query(), $request->rules())->passes());
        $this->assertSame($expected, $request->hasReply());
    }

    /** @return array<string, array{mixed}> */
    public static function invalidHasReplyProvider(): array
    {
        return [
            'true string' => ['true'],
            'false string' => ['false'],
            'other number' => ['2'],
            'boolean true' => [true],
            'boolean false' => [false],
        ];
    }

    #[DataProvider('invalidHasReplyProvider')]
    public function testHasReplyRejectsValuesOtherThanNumericQueryStrings(mixed $value): void
    {
        $request = ListContactsRequest::create('/api/site-management/contacts', 'GET', ['hasReply' => $value]);

        $this->assertTrue(Validator::make($request->query(), $request->rules())->fails());
    }

    public function testHasReplyMayBeOmitted(): void
    {
        $request = ListContactsRequest::create('/api/site-management/contacts');

        $this->assertTrue(Validator::make($request->query(), $request->rules())->passes());
        $this->assertNull($request->hasReply());
    }

    public function testPaginationValuesAreReadFromQuery(): void
    {
        $request = ListContactsRequest::create('/api/site-management/contacts?perPage=20&page=3');

        $this->assertTrue(Validator::make($request->query(), $request->rules())->passes());
        $this->assertSame(20, $request->perPage());
        $this->assertSame(3, $request->page());
    }

    public function testPaginationValuesAreOptional(): void
    {
        $request = ListContactsRequest::create('/api/site-management/contacts');

        $this->assertTrue(Validator::make($request->query(), $request->rules())->passes());
        $this->assertNull($request->perPage());
        $this->assertSame(1, $request->page());
    }

    /** @return array<string, array{string, string}> */
    public static function invalidPaginationProvider(): array
    {
        return [
            'zero per page' => ['0', '1'],
            'per page over maximum' => ['101', '1'],
            'zero page' => ['20', '0'],
        ];
    }

    #[DataProvider('invalidPaginationProvider')]
    public function testPaginationRejectsOutOfRangeQueryValues(string $perPage, string $page): void
    {
        $request = ListContactsRequest::create('/api/site-management/contacts?perPage='.$perPage.'&page='.$page);

        $this->assertTrue(Validator::make($request->query(), $request->rules())->fails());
    }
}
