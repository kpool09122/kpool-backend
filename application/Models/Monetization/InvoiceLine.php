<?php

declare(strict_types=1);

namespace Application\Models\Monetization;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property string $invoice_id
 * @property string $description
 * @property string $currency
 * @property int $unit_price
 * @property int $quantity
 * @property Carbon $created_at
 */
#[Fillable([
    'invoice_id',
    'description',
    'currency',
    'unit_price',
    'quantity',
])]
#[Table(name: 'invoice_lines')]
class InvoiceLine extends Model
{
    public const UPDATED_AT = null;

    #[Override]
    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }
}
