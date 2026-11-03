<?php

namespace App\Models;

use Database\Factories\EducationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $degree
 * @property string $school
 * @property string|null $institution
 * @property string $period
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['degree', 'school', 'institution', 'period', 'sort_order'])]
class Education extends Model
{
    /** @use HasFactory<EducationFactory> */
    use HasFactory;
}
