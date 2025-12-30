<?php

declare(strict_types=1);

namespace Application\Models\SiteManagement;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $table = 'contacts';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'category',
        'name',
        'email',
        'content',
    ];
}
