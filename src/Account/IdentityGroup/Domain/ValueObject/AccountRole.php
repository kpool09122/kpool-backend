<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Domain\ValueObject;

enum AccountRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MEMBER = 'member';
    case BILLING_CONTACT = 'billing_contact';
    case PRODUCT_OWNER = 'product_owner';

    public function canReviewVerification(): bool
    {
        return $this === self::PRODUCT_OWNER;
    }
}
