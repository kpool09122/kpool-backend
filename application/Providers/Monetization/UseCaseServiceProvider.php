<?php

declare(strict_types=1);

namespace Application\Providers\Monetization;

use Illuminate\Support\ServiceProvider;
use Source\Monetization\Account\Application\UseCase\Command\DeletePaymentMethod\DeletePaymentMethod;
use Source\Monetization\Account\Application\UseCase\Command\DeletePaymentMethod\DeletePaymentMethodInterface;
use Source\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration\PrepareCardRegistration;
use Source\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration\PrepareCardRegistrationInterface;
use Source\Monetization\Account\Application\UseCase\Command\ProvisionMonetizationAccount\ProvisionMonetizationAccount;
use Source\Monetization\Account\Application\UseCase\Command\ProvisionMonetizationAccount\ProvisionMonetizationAccountInterface;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethod;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodInterface;
use Source\Monetization\Account\Application\UseCase\Command\SyncPayoutAccount\SyncPayoutAccount;
use Source\Monetization\Account\Application\UseCase\Command\SyncPayoutAccount\SyncPayoutAccountInterface;
use Source\Monetization\Account\Application\UseCase\Query\GetPaymentMethod\GetPaymentMethodInterface;
use Source\Monetization\Account\Application\UseCase\Query\ListPaymentMethods\ListPaymentMethodsInterface;
use Source\Monetization\Account\Infrastructure\Query\GetPaymentMethod;
use Source\Monetization\Account\Infrastructure\Query\ListPaymentMethods;
use Source\Monetization\Billing\Application\UseCase\Command\CreateInvoice\CreateInvoice;
use Source\Monetization\Billing\Application\UseCase\Command\CreateInvoice\CreateInvoiceInterface;
use Source\Monetization\Billing\Application\UseCase\Command\RecordPayment\RecordPayment;
use Source\Monetization\Billing\Application\UseCase\Command\RecordPayment\RecordPaymentInterface;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePayment;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentInterface;
use Source\Monetization\Payment\Application\UseCase\Command\CapturePayment\CapturePayment;
use Source\Monetization\Payment\Application\UseCase\Command\CapturePayment\CapturePaymentInterface;
use Source\Monetization\Payment\Application\UseCase\Command\RefundPayment\RefundPayment;
use Source\Monetization\Payment\Application\UseCase\Command\RefundPayment\RefundPaymentInterface;
use Source\Monetization\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipal;
use Source\Monetization\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInterface;
use Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembers;
use Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInterface;
use Source\Monetization\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInterface;
use Source\Monetization\Principal\Application\UseCase\Query\ListMembers\ListMembersInterface;
use Source\Monetization\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInterface;
use Source\Monetization\Principal\Infrastructure\Query\GetCurrentPrincipal;
use Source\Monetization\Principal\Infrastructure\Query\ListMembers;
use Source\Monetization\Principal\Infrastructure\Query\ListPrincipalGroups;
use Source\Monetization\Settlement\Application\UseCase\Command\SettleRevenue\SettleRevenue;
use Source\Monetization\Settlement\Application\UseCase\Command\SettleRevenue\SettleRevenueInterface;

class UseCaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(PrepareCardRegistrationInterface::class, PrepareCardRegistration::class);
        $this->app->singleton(DeletePaymentMethodInterface::class, DeletePaymentMethod::class);
        $this->app->singleton(ListPaymentMethodsInterface::class, ListPaymentMethods::class);
        $this->app->singleton(GetPaymentMethodInterface::class, GetPaymentMethod::class);

        $this->app->singleton(CreatePrincipalInterface::class, CreatePrincipal::class);

        $this->app->singleton(GetCurrentPrincipalInterface::class, GetCurrentPrincipal::class);
        $this->app->singleton(ListPrincipalGroupsInterface::class, ListPrincipalGroups::class);
        $this->app->singleton(ListMembersInterface::class, ListMembers::class);

        $this->app->singleton(UpdatePrincipalGroupMembersInterface::class, UpdatePrincipalGroupMembers::class);

        // Account
        $this->app->singleton(ProvisionMonetizationAccountInterface::class, ProvisionMonetizationAccount::class);
        $this->app->singleton(RegisterPaymentMethodInterface::class, RegisterPaymentMethod::class);
        $this->app->singleton(SyncPayoutAccountInterface::class, SyncPayoutAccount::class);

        // Billing
        $this->app->singleton(CreateInvoiceInterface::class, CreateInvoice::class);
        $this->app->singleton(RecordPaymentInterface::class, RecordPayment::class);
        $this->app->singleton(AuthorizePaymentInterface::class, AuthorizePayment::class);
        $this->app->singleton(CapturePaymentInterface::class, CapturePayment::class);
        $this->app->singleton(RefundPaymentInterface::class, RefundPayment::class);
        $this->app->singleton(SettleRevenueInterface::class, SettleRevenue::class);
    }
}
