<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 */
class PrincipalGroup extends Model
{
    protected $table = 'site_management_principal_groups';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected $casts = ['statements' => 'array'];
}
