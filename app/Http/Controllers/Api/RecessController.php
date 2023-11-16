<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recess\StoreRequest;
use App\Http\Requests\Recess\UpdateRequest;
use App\Http\Resources\Recess\AllRecessResource;
use App\Models\Attendance;
use App\Models\Recess;
use Carbon\Carbon;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class RecessController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Recess::with('attendance');
            if (!empty($request->attendance_id))
                $query->where('attendance_id', $request->attendance_id);
            if (!empty($request->skip))
                $query->skip($request->skip);
            if (!empty($request->take))
                $query->take($request->take);
            $break = $query->orderBy('id', 'DESC')->get();
            return response()->json([
                'status' => true,
                'message' => ($break->count()) . " break(s) found",
                'data' => AllRecessResource::collection($break),
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
     * @param  \App\Http\Requests\Recess\StoreRequest  $request
     */
    public function store(StoreRequest $request)
    {
        try {
            DB::beginTransaction();
            $attendance = Attendance::where('id', $request->attendance_id)->first();
            if (empty($attendance))
                throw new Error('Please First Time in');

            $inputs = $request->except(
                'attendance_id',
                'date',
                'break_in',
                'break_out',
                'total_time',
                'break_type',
            );

            $today_date = Carbon::today();
            $break_in = Carbon::parse($request->break_in);

            if (!empty($request->break_out)) {
                $attendance_inputs = [];

                $break_out = Carbon::parse($request->break_out);
                $breakDuration = $break_out->diff($break_in);
                $expected_time_out = Carbon::parse($attendance->expected_time_out);
                $break_time = Carbon::createFromTime($breakDuration->h, $breakDuration->i, $breakDuration->s);

                if (!empty($attendance->time_out)) {
                    $working_time = Carbon::parse($attendance->working_time);

                    $given_working_time = $working_time->subHours($break_time->format('h'))->subMinutes($break_time->format('i'))->subSeconds($break_time->format('s'));
                    // Calculate the difference in minutes
                    $minutesDifference = $given_working_time->diffInMinutes(Carbon::parse('00:00:00'));

                    // Calculate the difference between time in and time out
                    $hoursDifference = $minutesDifference / 60;

                    // Round the result to the nearest half-hour
                    $timeHours = round($hoursDifference, 2);

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
                    $attendance_inputs['working_time'] = $working_time->format('H:i:s');
                    $attendance_inputs['status'] = $status;
                }

                $attendance_inputs['expected_time_out'] = $expected_time_out->addHours($breakDuration->h)->addMinutes($breakDuration->i)->addSeconds($breakDuration->s);
                $attendance->update($attendance_inputs);

                $inputs['break_out'] = $break_out->format('H:i:s');
                $inputs['total_time'] = $break_time;
            }

            $inputs['attendance_id'] = $attendance->id;
            $inputs['break_in'] = $break_in->format('H:i:s');
            $inputs['date'] = $today_date;
            $break = Recess::create($inputs);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Recess Add Successfully.",
                'break' => new AllRecessResource($break),
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
     * @param  \App\Models\Recess $break
     */
    public function show(Recess $break)
    {
        if (empty($break)) {
            return response()->json([
                'status' => false,
                'message' => "Recess not found",
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => "Recess has been successfully found",
            'break' => new AllRecessResource($break),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @param  \App\Http\Requests\Recess\UpdateRequest  $request
     * @param  \App\Models\Recess $break
     */
    public function update(UpdateRequest $request, Recess $break)
    {
        if (empty($break)) {
            return response()->json([
                'status' => false,
                'message' => "Recess not found",
            ], 404);
        }

        try {
            DB::beginTransaction();
            $attendance = Attendance::where('id', $break->attendance_id)->first();
            if (empty($attendance))
                throw new Error('Please First Time in');

            $inputs = $request->except(
                'date',
                'break_in',
                'break_out',
                'total_time',
                'break_type',
            );

            $today_date = Carbon::today();
            if (!empty($request->break_in)) {
                $break_in = Carbon::parse($request->break_in);
            } else {
                $break_in = Carbon::parse($break->break_in);
            }
            if (!empty($request->break_out))
                $break_out = Carbon::parse($request->break_out);
            else
                $break_out = Carbon::parse($break->break_out);

            if (!empty($break_out)) {
                $attendance_inputs = [];

                $breakDuration = $break_out->diff($break_in);
                $expected_time_out = Carbon::parse($attendance->expected_time_out);
                $break_time = Carbon::createFromTime($breakDuration->h, $breakDuration->i, $breakDuration->s);

                if (!empty($attendance->time_out)) {
                    $working_time = Carbon::parse($attendance->working_time);

                    $given_working_time = $working_time->subHours($break_time->format('h'))->subMinutes($break_time->format('i'))->subSeconds($break_time->format('s'));
                    // Calculate the difference in minutes
                    $minutesDifference = $given_working_time->diffInMinutes(Carbon::parse('00:00:00'));

                    // Calculate the difference between time in and time out
                    $hoursDifference = $minutesDifference / 60;

                    // Round the result to the nearest half-hour
                    $timeHours = round($hoursDifference, 2);

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
                    $attendance_inputs['working_time'] = $working_time->format('H:i:s');
                    $attendance_inputs['status'] = $status;
                }

                $attendance_inputs['expected_time_out'] = $expected_time_out->addHours($breakDuration->h)->addMinutes($breakDuration->i)->addSeconds($breakDuration->s);
                $attendance->update($attendance_inputs);

                $inputs['break_out'] = $break_out->format('H:i:s');
                $inputs['total_time'] = $break_time;
            }

            $inputs['break_in'] = $break_in->format('H:i:s');
            $break->update($inputs);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Break Update Successfully.",
                'break' => new AllRecessResource($break),
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
     * @param  \App\Models\Recess $break
     */
    public function destroy(Recess $break)
    {
        if (empty($break)) {
            return response()->json([
                'status' => false,
                'message' => "Recess not found",
            ], 404);
        }

        try {
            DB::beginTransaction();
            $break->delete();
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Recess has been successfully deleted",
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
