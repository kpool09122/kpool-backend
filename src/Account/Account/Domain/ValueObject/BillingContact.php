<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\Email;

readonly class BillingContact
{
    public function __construct(
        private ContractName $name,
        private Email        $email,
        private ?Phone      $phone = null,
    ) {
    }

    public function name(): ContractName
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function phone(): ?Phone
    {
        return $this->phone;
    }
}
