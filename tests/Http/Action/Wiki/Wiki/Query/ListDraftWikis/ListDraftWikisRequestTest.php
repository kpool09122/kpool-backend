<?php

declare(strict_types=1);

namespace Tests\Http\Action\Wiki\Wiki\Query\ListDraftWikis;

use Application\Http\Action\Wiki\Wiki\Query\ListDraftWikis\ListDraftWikisRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Tests\TestCase;

class ListDraftWikisRequestTest extends TestCase
{
    #[DataProvider('validOnlyMineProvider')]
    public function testRulesPassesWhenOnlyMineIsBooleanLikeQueryValue(mixed $onlyMine): void
    {
        $payload = $this->validPayload();
        $payload['onlyMine'] = $onlyMine;

        $validator = Validator::make($payload, (new ListDraftWikisRequest())->rules());

        $this->assertTrue($validator->passes());
    }

    public function testRulesFailsWhenOnlyMineIsInvalidValue(): void
    {
        $payload = $this->validPayload();
        $payload['onlyMine'] = 'yes';

        $validator = Validator::make($payload, (new ListDraftWikisRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('onlyMine', $validator->errors()->toArray());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function validOnlyMineProvider(): iterable
    {
        yield 'query true' => ['true'];
        yield 'query false' => ['false'];
        yield 'query 1' => ['1'];
        yield 'query 0' => ['0'];
        yield 'boolean true' => [true];
        yield 'boolean false' => [false];
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'status' => ApprovalStatus::UnderReview->value,
            'perPage' => 12,
        ];
    }
}
