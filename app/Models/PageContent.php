<?php

namespace App\Models;

use Database\Factories\PageContentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property string $title
 * @property string|null $description
 * @property string|null $image_alt
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'title', 'description', 'image_alt'])]
class PageContent extends Model
{
    /** @use HasFactory<PageContentFactory> */
    use HasFactory;
}
