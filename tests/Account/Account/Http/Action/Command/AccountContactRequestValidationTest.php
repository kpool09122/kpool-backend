<?php

declare(strict_types=1);

namespace Tests\Account\Account\Http\Action\Command;

use Application\Http\Action\Account\Account\Command\CreateAccount\CreateAccountRequest;
use Application\Http\Action\Account\Account\Command\UpdateAccount\UpdateAccountRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccountContactRequestValidationTest extends TestCase
{
    /**
     * @param class-string<CreateAccountRequest|UpdateAccountRequest> $requestClass
     * @param array<string, mixed> $payload
     */
    #[DataProvider('requestPayloadProvider')]
    public function testAdministrativeAreaCodeIsRejectedWhenCountryCodeIsNull(string $requestClass, array $payload): void
    {
        $request = $requestClass::create('/', 'POST', $payload);
        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('address.administrativeAreaCode', $validator->errors()->toArray());
    }

    /**
     * @param class-string<CreateAccountRequest|UpdateAccountRequest> $requestClass
     * @param array<string, mixed> $payload
     */
    #[DataProvider('validRequestPayloadProvider')]
    public function testAdministrativeAreaCodeIsAllowedWhenCountryCodeIsPresent(string $requestClass, array $payload): void
    {
        $request = $requestClass::create('/', 'POST', $payload);
        $validator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($validator->fails(), (string) $validator->errors());
    }

    /**
     * @return iterable<string, array{class-string<CreateAccountRequest|UpdateAccountRequest>, array<string, mixed>}>
     */
    public static function requestPayloadProvider(): iterable
    {
        yield 'create account' => [CreateAccountRequest::class, self::createPayload([
            'countryCode' => null,
            'administrativeAreaCode' => '13',
        ])];

        yield 'update account' => [UpdateAccountRequest::class, self::updatePayload([
            'countryCode' => null,
            'administrativeAreaCode' => '13',
        ])];
    }

    /**
     * @return iterable<string, array{class-string<CreateAccountRequest|UpdateAccountRequest>, array<string, mixed>}>
     */
    public static function validRequestPayloadProvider(): iterable
    {
        yield 'create account' => [CreateAccountRequest::class, self::createPayload([
            'countryCode' => 'JP',
            'administrativeAreaCode' => '13',
        ])];

        yield 'update account' => [UpdateAccountRequest::class, self::updatePayload([
            'countryCode' => 'JP',
            'administrativeAreaCode' => '13',
        ])];
    }

    /**
     * @param array<string, mixed> $addressOverrides
     * @return array<string, mixed>
     */
    private static function createPayload(array $addressOverrides): array
    {
        return [
            'email' => 'account@example.com',
            'accountType' => 'corporation',
            'accountName' => 'Account Name',
            'identityIdentifier' => null,
            'phone' => '+81-90-1234-5678',
            'address' => array_merge([
                'postalCode' => '100-0001',
                'locality' => '千代田区',
                'addressLine1' => '千代田1-1',
                'addressLine2' => null,
            ], $addressOverrides),
        ];
    }

    /**
     * @param array<string, mixed> $addressOverrides
     * @return array<string, mixed>
     */
    private static function updatePayload(array $addressOverrides): array
    {
        return [
            'accountName' => 'Updated Account Name',
            'phone' => '+81-90-1234-5678',
            'address' => array_merge([
                'postalCode' => '100-0001',
                'locality' => '千代田区',
                'addressLine1' => '千代田1-1',
                'addressLine2' => null,
            ], $addressOverrides),
        ];
    }
}
