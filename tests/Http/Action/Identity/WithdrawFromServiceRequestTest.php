<?php

declare(strict_types=1);

namespace Tests\Http\Action\Identity;

use Application\Http\Action\Identity\Command\WithdrawFromService\WithdrawFromServiceRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WithdrawFromServiceRequestTest extends TestCase
{
    /** @return array<string, array{array<string, mixed>}> */
    public static function invalidBodies(): array
    {
        return [
            'missing' => [[]],
            'null' => [['confirmationIdentityName' => null]],
            'empty' => [['confirmationIdentityName' => '']],
            'non-string' => [['confirmationIdentityName' => 123]],
            'too long' => [['confirmationIdentityName' => str_repeat('a', 33)]],
        ];
    }

    /** @param array<string, mixed> $body */
    #[DataProvider('invalidBodies')]
    public function testConfirmationIsRequired(array $body): void
    {
        $this->assertTrue(Validator::make($body, (new WithdrawFromServiceRequest())->rules())->fails());
    }

    public function testPreservesTheExactEnteredName(): void
    {
        $request = WithdrawFromServiceRequest::create('/', 'DELETE', ['confirmationIdentityName' => ' User Name ']);

        $this->assertTrue(Validator::make($request->all(), $request->rules())->passes());
        $this->assertSame(' User Name ', $request->confirmationIdentityName());
    }
}
