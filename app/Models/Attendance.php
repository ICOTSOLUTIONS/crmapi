<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_id',
        'time_in',
        'time_out',
        'working_time',
        'date',
        'status',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id', 'id');
    }

    public function break()
    {
        return $this->hasMany(Recess::class, 'attendance_id', 'id');
    }
}
