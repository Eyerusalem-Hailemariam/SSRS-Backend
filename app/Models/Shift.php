<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Shift",
 *     type="object",
 *     title="Shift",
 *     description="Shift model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Morning Shift"),
 *     @OA\Property(property="start_time", type="string", format="time", example="08:00:00"),
 *     @OA\Property(property="end_time", type="string", format="time", example="16:00:00")
 * )
 */


class Shift extends Model
{
    use HasFactory;
    protected $fillable = [
        'staff_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'is_overtime',
    ];
    public function staff() {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}

