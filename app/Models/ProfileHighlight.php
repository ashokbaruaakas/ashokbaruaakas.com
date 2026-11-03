<?php

namespace App\Models;

use Database\Factories\ProfileHighlightFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $label
 * @property string $value
 * @property string|null $detail
 * @property string|null $short_detail
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['label', 'value', 'detail', 'short_detail', 'sort_order'])]
class ProfileHighlight extends Model
{
    /** @use HasFactory<ProfileHighlightFactory> */
    use HasFactory;
}
