<?php

namespace App\Models;

use App\Enums\CreditTransactionType;
use Database\Factories\CreditTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $credit_wallet_id
 * @property int|null $user_id
 * @property CreditTransactionType $type
 * @property int $amount
 * @property int $available_balance_after
 * @property int $reserved_balance_after
 * @property string|null $idempotency_key
 * @property string|null $description
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $anonymised_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'credit_wallet_id',
    'user_id',
    'type',
    'amount',
    'available_balance_after',
    'reserved_balance_after',
    'idempotency_key',
    'description',
    'metadata',
    'anonymised_at',
])]
class CreditTransaction extends Model
{
    /** @use HasFactory<CreditTransactionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => CreditTransactionType::class,
            'metadata' => 'array',
            'anonymised_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CreditWallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(CreditWallet::class, 'credit_wallet_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
