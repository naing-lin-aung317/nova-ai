<?php

namespace App\Models;

use Database\Factories\CreditWalletFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int $available_balance
 * @property int $reserved_balance
 * @property Carbon|null $anonymised_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'available_balance',
    'reserved_balance',
    'anonymised_at',
])]
class CreditWallet extends Model
{
    /** @use HasFactory<CreditWalletFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'anonymised_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<CreditTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }
}
