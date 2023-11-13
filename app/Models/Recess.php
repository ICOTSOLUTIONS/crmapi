<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recess extends Model
{
    use HasFactory;
    protected $fillable = [
        'attendance_id',
        'break_in',
        'break_out',
        'break_type',
        'total_time',
        'date',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id', 'id');
    }
}
