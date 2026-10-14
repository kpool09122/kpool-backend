<?php

declare(strict_types=1);

namespace Application\Models\Account;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $account_id
 * @property string $identity_id
 */
#[Fillable(['account_id', 'identity_id'])]
#[Table(name: 'account_notification_recipients')]
class NotificationRecipient extends Model
{
    public $timestamps = false;
}
