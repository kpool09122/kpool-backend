<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\CreatePolicy;

use DateTimeImmutable;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePolicy\CreatePolicyOutput;
use Source\Wiki\Principal\Domain\Entity\Policy;
use Source\Wiki\Principal\Domain\ValueObject\Effect;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\Statement;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreatePolicyOutputTest extends TestCase
{
    /**
     * 正常系: PolicyがセットされるとtoArrayが正しい値を返すこと.
     */
    public function testToArrayWithPolicy(): void
    {
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $name = 'Test Policy';
        $isSystemPolicy = false;
        $createdAt = new DateTimeImmutable();

        $policy = new Policy(
            $policyIdentifier,
            $name,
            [
                new Statement(
                    Effect::ALLOW,
                    [Action::CREATE],
                    ResourceType::cases(),
                    null,
                ),
            ],
            $isSystemPolicy,
            $createdAt,
        );

        $output = new CreatePolicyOutput();
        $output->setPolicy($policy);

        $result = $output->toArray();

        $this->assertSame((string) $policyIdentifier, $result['policyIdentifier']);
        $this->assertSame($name, $result['name']);
        $this->assertSame($isSystemPolicy, $result['isSystemPolicy']);
        $this->assertSame($createdAt->format('Y-m-d\TH:i:sP'), $result['createdAt']);
    }

    /**
     * 正常系: Policyが未セットの場合toArrayがnull値の配列を返すこと.
     */
    public function testToArrayWithoutPolicy(): void
    {
        $output = new CreatePolicyOutput();

        $result = $output->toArray();

        $this->assertNull($result['policyIdentifier']);
        $this->assertNull($result['name']);
        $this->assertNull($result['isSystemPolicy']);
        $this->assertNull($result['createdAt']);
    }
}
