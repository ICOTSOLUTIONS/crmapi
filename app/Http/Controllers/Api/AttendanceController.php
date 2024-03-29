<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StoreRequest;
use App\Http\Requests\Attendance\UpdateRequest;
use App\Http\Resources\Attendance\AllAttendanceResource;
use App\Models\Attendance;
use App\Models\Recess;
use Carbon\Carbon;
use Error;
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
            $user = auth()->user();
            $fromDate = (!empty($request->from_date)) ? $request->from_date : '';
            $toDate = (!empty($request->to_date)) ? $request->to_date : '';
            $query = Attendance::with('employee', 'break');
            if (!empty($user) && $user->role_id == 3)
                $query->where('employee_id', $user->id);
            if (!empty($request->employee_id) && isset($request->employee_id))
                $query->where('employee_id', $request->employee_id);
            if (!empty($fromDate) && !empty($toDate))
                $query->whereBetween('date', [$fromDate, $toDate]);
            if (!empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('date', $search)
                        ->orWherehas('employee', function ($q1) use ($search) {
                            return $q1->where(function ($q2) use ($search) {
                                $q2->orWhere('first_name', $search)
                                    ->orWhere('last_name', $search)
                                    ->orWhere('phone', $search)
                                    ->orWhere('status', $search)
                                    ->orWhere('email', $search);
                            });
                        });
                });
            }
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
            $attendance = Attendance::where('employee_id', auth()->user()->id)->where('date', $today_date)->first();
            if (!empty($attendance))
                throw new Error('Already Time in');

            $inputs = $request->except(
                'employee_id',
                'date',
                'time_in',
                'time_out',
                'status',
                'working_time',
                'expected_time_out',
            );

            $time_in = Carbon::parse($request->time_in);
            $late_time = Carbon::createFromTime(14, 19, 0); // 2:19 PM

            if ($time_in->gt($late_time)) {
                // The employee is late
                $status = 'late';
            } else {
                // The employee is on time or early
                $status = 'present';
            }

            if (!empty($request->time_out)) {
                $time_out = Carbon::parse($request->time_out);
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
                    if ($status == 'late')
                        $status = 'late_and_short';
                    else
                        $status = 'short_day';
                } elseif ($timeHours >= 8.5) {
                    // Some other condition that you want to handle
                    if ($status == 'late')
                        $status = 'late';
                    else
                        $status = 'present';
                }
                $inputs['time_out'] = $time_out->format('H:i:s');
                $inputs['working_time'] = Carbon::createFromTime($duration->h, $duration->i, $duration->s);
            }

            $inputs['employee_id'] = auth()->user()->id;
            $inputs['time_in'] = $time_in->format('H:i:s');
            $inputs['expected_time_out'] = $time_in->addHours(8)->addMinutes(30)->format('H:i:s');
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
            'attendance' => new AllAttendanceResource($attendance->load('employee', 'break')),
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
            $inputs = $request->except(
                'time_in',
                'time_out',
                'status',
                'working_time',
                'expected_time_out',
            );

            // Assuming $timeIn is the time the employee clocked in (e.g., '2:00 PM')
            if (!empty($request->time_in)) {
                $time_in = Carbon::parse($request->time_in);

                $late_time = Carbon::createFromTime(14, 19, 0); // 2:19 PM

                if ($time_in->gt($late_time)) {
                    // The employee is late
                    $status = 'late';
                } else {
                    // The employee is on time or early
                    $status = 'present';
                }
            } else {
                $time_in = Carbon::parse($attendance->time_in);
            }

            $breaks = Attendance::with('break')->find($attendance->id)
                ->break()
                ->selectRaw('SUM(TIME_TO_SEC(total_time)) as total_seconds')
                ->pluck('total_seconds')
                ->first();

            if (empty($breaks))
                $breaks = 0;

            if (!empty($request->time_out))
                $time_out = Carbon::parse($request->time_out);

            if (!empty($time_out)) {
                // Calculate the difference in minutes
                $minutesDifference = $time_out->diffInMinutes($time_in);

                $minutesDifference += (int) $breaks / 60;
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
                $inputs['working_time'] = Carbon::createFromTime($duration->h, $duration->i, $duration->s)->subSeconds((int) $breaks);
                $inputs['time_out'] = $time_out->format('H:i:s');
            }

            $inputs['status'] = $status;
            $inputs['time_in'] = $time_in->format('H:i:s');
            $inputs['expected_time_out'] = $time_in->addHours(8)->addMinutes(30)->addSeconds((int) $breaks)->format('H:i:s');

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

    public function check()
    {
        try {
            DB::beginTransaction();
            $time_in = false;
            $user = auth()->user();

            $query = Attendance::query();
            if (!empty($user) && $user->role_id == 3)
                $query->where('employee_id', $user->id);
            $attendance = $query->whereDate('date', now()->toDateString())->first();
            if (!empty($attendance)) {
                if (empty($attendance->time_out))
                    $time_in = true;
            }
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Attendance has been successfully found",
                'time_in' => $time_in,
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

