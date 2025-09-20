<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $translation
 * @property string $name
 * @property string $CEO
 * @property ?Carbon $founded_in
 * @property string $description
 */
class Agency extends Model
{
    public $incrementing = false;

    protected $table = 'agencies';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'translation',
        'name',
        'CEO',
        'founded_in',
        'description',
    ];
}
