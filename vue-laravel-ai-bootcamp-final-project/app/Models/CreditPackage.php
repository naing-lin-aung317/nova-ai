<?php

namespace App\Models;

use Database\Factories\CreditPackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $credits
 * @property int $price_cents
 * @property string $currency
 * @property bool $is_visible
 * @property int $sort_order
 */
#[Fillable([
    'name',
    'slug',
    'credits',
    'price_cents',
    'currency',
    'is_visible',
    'sort_order',
])]
class CreditPackage extends Model
{
    /** @use HasFactory<CreditPackageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    /**
     * @return HasMany<PaymentOrder, $this>
     */
    public function paymentOrders(): HasMany
    {
        return $this->hasMany(PaymentOrder::class);
    }

    public function priceUsd(): string
    {
        return number_format($this->price_cents / 100, 2, '.', '');
    }
}
