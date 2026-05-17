<?php

declare(strict_types=1);

namespace Tests\Http\Action\Wiki\Image\Command\UploadImage;

use Application\Http\Action\Wiki\Image\Command\UploadImage\UploadImageRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class UploadImageRequestTest extends TestCase
{
    public function testRulesPassesWhenRightsConfirmationAgreedIsTrue(): void
    {
        $validator = Validator::make($this->validPayload(), (new UploadImageRequest())->rules());

        $this->assertTrue($validator->passes());
    }

    #[DataProvider('invalidRightsConfirmationAgreedProvider')]
    public function testRulesFailsWhenRightsConfirmationAgreedIsNotTrue(mixed $value): void
    {
        $payload = $this->validPayload();
        if ($value !== '__missing__') {
            $payload['rightsConfirmationAgreed'] = $value;
        } else {
            unset($payload['rightsConfirmationAgreed']);
        }

        $validator = Validator::make($payload, (new UploadImageRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('rightsConfirmationAgreed', $validator->errors()->toArray());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidRightsConfirmationAgreedProvider(): iterable
    {
        yield 'missing' => ['__missing__'];
        yield 'false' => [false];
        yield 'null' => [null];
        yield 'string' => ['not-agreed'];
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'publishedImageIdentifier' => null,
            'resourceType' => ResourceType::TALENT->value,
            'translationSetIdentifier' => StrTestHelper::generateUuid(),
            'base64EncodedImage' => 'base64-image',
            'displayOrder' => 1,
            'sourceUrl' => 'https://example.com/source',
            'sourceName' => 'Example Source',
            'altText' => 'Profile image',
            'agreedToTermsAt' => '2024-01-01 00:00:00',
            'rightsConfirmationAgreed' => true,
        ];
    }
}
