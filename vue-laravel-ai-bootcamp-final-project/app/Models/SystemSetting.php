<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string $value
 */
#[Fillable(['key', 'value'])]
class SystemSetting extends Model
{
    /** @var list<string> */
    protected $fillable = ['key', 'value'];
}
