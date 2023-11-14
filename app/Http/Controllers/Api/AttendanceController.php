<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StoreRequest;
use App\Http\Requests\Attendance\UpdateRequest;
use App\Http\Resources\Attendance\AllAttendanceResource;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Attendance::with('employee');
            if (!empty($request->employee_id))
                $query->where('employee_id', $request->employee_id);
            if (!empty($request->skip))
                $query->skip($request->skip);
            if (!empty($request->take))
                $query->take($request->take);
            $attendance = $query->orderBy('id', 'DESC')->get();
            return response()->json([
                'status' => true,
                'message' => ($attendance->count()) . " attendance(s) found",
                'data' => AllAttendanceResource::collection($attendance),
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param  \App\Http\Requests\Attendance\StoreRequest  $request
     */
    public function store(StoreRequest $request)
    {
        try {
            DB::beginTransaction();
            $today_date = Carbon::today();
            $time_in = Carbon::now();
            $late_time = Carbon::createFromTime(14, 19, 0); // 2:19 PM

            if ($time_in->gt($late_time)) {
                // The employee is late
                $status = 'late';
            } else {
                // The employee is on time or early
                $status = 'present';
            }

            $inputs = $request->except(
                'employee_id',
                'time_in',
                'expected_time_out',
                'working_time',
                'date',
                'status',
            );
            $inputs['employee_id'] = auth()->user()->id;
            $inputs['time_in'] = $time_in->format('Y-m-d H:i:s');
            $inputs['expected_time_out'] = $time_in->addHours(8)->addMinutes(30);
            $inputs['status'] = $status;
            $inputs['date'] = $today_date;
            $attendance = Attendance::create($inputs);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Attendance Add Successfully.",
                'attendance' => new AllAttendanceResource($attendance),
            ]);
        } catch (Throwable $th) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * @param  \App\Models\Attendance $attendance
     */
    public function show(Attendance $attendance)
    {
        if (empty($attendance)) {
            return response()->json([
                'status' => false,
                'message' => "Attendance not found",
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => "Attendance has been successfully found",
            'attendance' => new AllAttendanceResource($attendance),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @param  \App\Http\Requests\Attendance\UpdateRequest  $request
     * @param  \App\Models\Attendance $attendance
     */
    public function update(UpdateRequest $request, Attendance $attendance)
    {
        if (empty($attendance)) {
            return response()->json([
                'status' => false,
                'message' => "Attendance not found",
            ], 404);
        }

        try {
            DB::beginTransaction();
            // Assuming $timeIn is the time the employee clocked in (e.g., '2:00 PM')
            if (!empty($request->time_in))
                $time_in = Carbon::parse($request->time_in);
            else
                $time_in = Carbon::parse($attendance->time_in);

            if (!empty($request->time_out))
                $time_out = Carbon::parse($request->time_out);
            else if (!empty($attendance->time_out))
                $time_out = Carbon::parse($attendance->time_out);
            else
                $time_out = Carbon::now();

            // Calculate the difference in minutes
            $minutesDifference = $time_out->diffInMinutes($time_in);

            // Calculate the difference between time in and time out
            $hoursDifference = $minutesDifference / 60;

            // Round the result to the nearest half-hour
            $timeHours = round($hoursDifference, 2);

            $duration = $time_out->diff($time_in);

            // Check the difference and set the status
            if ($timeHours < 4) {
                // The employee is absent
                $status = 'absent';
            } elseif ($timeHours >= 4 && $timeHours < 6) {
                // The employee is present for half of the shift
                $status = 'half_day';
            } elseif ($timeHours >= 6 && $timeHours < 8.5) {
                // The employee is present for more than 6 hours
                if ($attendance->status == 'late')
                    $status = 'late_and_short';
                else
                    $status = 'short_day';
            } elseif ($timeHours >= 8.5) {
                // Some other condition that you want to handle
                if ($attendance->status == 'late')
                    $status = 'late';
                else
                    $status = 'present';
            }

            $inputs = $request->except(
                'working_time',
                'status',
                'time_out',
                'expected_time_out',
            );
            $inputs['working_time'] = Carbon::create($time_out->year, $time_out->month, $time_out->day, $duration->h, $duration->i, $duration->s);
            $inputs['status'] = $status;
            $inputs['time_in'] = $time_in->format('Y-m-d H:i:s');
            $inputs['time_out'] = $time_out->format('Y-m-d H:i:s');
            $inputs['expected_time_out'] = $time_in->addHours(8)->addMinutes(30);

            $attendance->update($inputs);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Attendance has been successfully updated",
                'attendance' => new AllAttendanceResource($attendance),
            ]);
        } catch (Throwable $th) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param  \App\Models\Attendance $attendance
     */
    public function destroy(Attendance $attendance)
    {
        if (empty($attendance)) {
            return response()->json([
                'status' => false,
                'message' => "Attendance not found",
            ], 404);
        }

        try {
            DB::beginTransaction();
            $attendance->delete();
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Attendance has been successfully deleted",
            ]);
        } catch (Throwable $th) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}

