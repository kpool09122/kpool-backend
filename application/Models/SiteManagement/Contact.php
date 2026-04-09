<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    #[\Override]
    protected $table = 'contacts';

    #[\Override]
    protected $keyType = 'string';

    #[\Override]
    public $incrementing = false;

    /**
     * @var array<int, string>
     */
    #[\Override]
    protected $fillable = [
        'id',
        'category',
        'identity_identifier',
        'name',
        'email',
        'content',
    ];
}
