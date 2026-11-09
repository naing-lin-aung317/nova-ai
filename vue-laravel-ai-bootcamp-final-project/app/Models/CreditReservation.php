<?php

namespace App\Models;

use App\Enums\CreditReservationStatus;
use Database\Factories\CreditReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $credit_wallet_id
 * @property int $user_id
 * @property int $generation_request_id
 * @property int $amount
 * @property CreditReservationStatus $status
 * @property string $idempotency_key
 * @property Carbon $expires_at
 * @property Carbon|null $released_at
 * @property Carbon|null $captured_at
 */
#[Fillable([
    'credit_wallet_id',
    'user_id',
    'generation_request_id',
    'amount',
    'status',
    'idempotency_key',
    'expires_at',
    'released_at',
    'captured_at',
])]
class CreditReservation extends Model
{
    /** @use HasFactory<CreditReservationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => CreditReservationStatus::class,
            'expires_at' => 'datetime',
            'released_at' => 'datetime',
            'captured_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<GenerationRequest, $this>
     */
    public function generationRequest(): BelongsTo
    {
        return $this->belongsTo(GenerationRequest::class);
    }

    /**
     * @return BelongsTo<CreditWallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(CreditWallet::class, 'credit_wallet_id');
    }
}
