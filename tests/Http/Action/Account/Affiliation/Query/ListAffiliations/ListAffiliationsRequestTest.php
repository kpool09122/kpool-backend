<?php

declare(strict_types=1);

namespace Tests\Http\Action\Account\Affiliation\Query\ListAffiliations;

use Application\Http\Action\Account\Affiliation\Query\ListAffiliations\ListAffiliationsRequest;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ListAffiliationsRequestTest extends TestCase
{
    /** @return array<string, array{?string, bool}> */
    public static function filterCases(): array
    {
        return [
            'true' => ['true', true],
            'false' => ['false', false],
            'one' => ['1', true],
            'zero' => ['0', false],
            'omitted' => [null, false],
        ];
    }

    #[DataProvider('filterCases')]
    public function testAcceptsBooleanQueryValues(?string $value, bool $expected): void
    {
        Route::get('/test/affiliations', static fn (ListAffiliationsRequest $request): array => ['exclude' => $request->excludeOpenDelegations()]);
        $url = '/test/affiliations'.($value === null ? '' : '?excludeOpenDelegations='.$value);
        $this->getJson($url)->assertOk()->assertJsonPath('exclude', $expected);
    }

    public function testRejectsInvalidFilterValue(): void
    {
        Route::get('/test/affiliations', static fn (ListAffiliationsRequest $request): array => ['exclude' => $request->excludeOpenDelegations()]);
        $this->getJson('/test/affiliations?excludeOpenDelegations=pending')->assertUnprocessable()->assertJsonValidationErrors(['excludeOpenDelegations']);
    }
}
