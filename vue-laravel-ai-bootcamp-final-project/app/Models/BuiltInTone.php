<?php

namespace App\Models;

use Database\Factories\BuiltInToneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'description', 'is_active', 'sort_order'])]
class BuiltInTone extends Model
{
    /** @use HasFactory<BuiltInToneFactory> */
    use HasFactory;
}
