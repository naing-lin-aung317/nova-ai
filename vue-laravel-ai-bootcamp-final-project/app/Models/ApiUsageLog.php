<?php

namespace App\Models;

use Database\Factories\ApiUsageLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $generation_request_id
 * @property int $duration_ms
 * @property bool $success
 * @property string|null $error_code
 */
#[Fillable([
    'generation_request_id',
    'duration_ms',
    'success',
    'error_code',
])]
class ApiUsageLog extends Model
{
    /** @use HasFactory<ApiUsageLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<GenerationRequest, $this>
     */
    public function generationRequest(): BelongsTo
    {
        return $this->belongsTo(GenerationRequest::class);
    }
}
