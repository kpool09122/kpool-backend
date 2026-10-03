<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\DeletionBlockReason;

class DeletionBlockReasonTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'UNPAID_INVOICES' => 'unpaid_invoices',
            'ACTIVE_SUBSCRIPTION' => 'active_subscription',
            'REFUND_POLICY_UNDECIDED' => 'refund_policy_undecided',
            'EXTERNAL_BILLING_DEPENDENCIES' => 'external_billing_dependencies',
            'OWNERSHIP_UNCONFIRMED' => 'ownership_unconfirmed',
            'PRIVILEGED_ASSETS_NOT_TRANSFERRED' => 'privileged_assets_not_transferred',
            'LEGAL_HOLD' => 'legal_hold',
            'RETENTION_REQUIREMENT' => 'retention_requirement',
            'AUDIT_IN_PROGRESS' => 'audit_in_progress',
            'DATA_EXPORT_PENDING' => 'data_export_pending',
            'EXTERNAL_INTEGRATIONS_ACTIVE' => 'external_integrations_active',
            'SHARED_DEPENDENCIES' => 'shared_dependencies',
            'BACKUP_POLICY_UNDECIDED' => 'backup_policy_undecided',
            'OPEN_TICKETS' => 'open_tickets',
            'SCHEDULED_JOBS_ACTIVE' => 'scheduled_jobs_active',
            'SECURITY_CREDENTIALS_ACTIVE' => 'security_credentials_active',
            'SSO_STILL_ACTIVE' => 'sso_still_active',
        ], array_column(DeletionBlockReason::cases(), 'value', 'name'));
    }
}
