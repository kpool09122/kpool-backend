<?php

declare(strict_types=1);

namespace Application\Models\Wiki;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property string $id
 * @property string $principal_id
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['id', 'principal_id', 'name', 'created_at', 'updated_at'])]
#[Table(name: 'wiki_playlists', keyType: 'string')]
class Playlist extends Model
{
    #[Override]
    public $incrementing = false;
    #[Override]
    public $timestamps = false;
    #[Override]
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];
}
