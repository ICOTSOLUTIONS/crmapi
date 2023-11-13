<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreRequest;
use App\Http\Requests\Employee\UpdateRequest;
use App\Http\Resources\Employee\AllEmployeeResource;
use App\Models\User;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = User::with('role')->where('role_id', 2);
            if (!empty($request->skip))
                $query->skip($request->skip);
            if (!empty($request->take))
                $query->take($request->take);
            $employee = $query->orderBy('id', 'DESC')->get();
            return response()->json([
                'status' => true,
                'message' => ($employee->count()) . " employee(s) found",
                'data' => AllEmployeeResource::collection($employee),
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
     * @param  \App\Http\Requests\Employee\StoreRequest  $request
     */
    public function store(StoreRequest $request)
    {
        try {
            DB::beginTransaction();
            $inputs = $request->except(
                'role_id',
                'image',
                'password',
            );
            $inputs['role_id'] = 2;
            $inputs['password'] = Hash::make($request->password);
            if (!empty($request->image)) {
                $image = $request->image;
                $filename = "Profile-Photo" . time() . "-" . rand() . "." . $image->getClientOriginalExtension();
                $image->storeAs('employee', $filename, "public");
                $inputs['image'] = "employee/" . $filename;
            }
            $employee = User::create($inputs);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Employee Add Successfully.",
                'employee' => new AllEmployeeResource($employee),
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
     * @param  \App\Models\User $employee
     */
    public function show(User $employee)
    {
        if (empty($employee) || $employee->role_id != 2) {
            return response()->json([
                'status' => false,
                'message' => "Employee not found",
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => "Employee has been successfully found",
            'employee' => new AllEmployeeResource($employee),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @param  \App\Http\Requests\Employee\UpdateRequest  $request
     * @param  \App\Models\User $employee
     */
    public function update(UpdateRequest $request, User $employee)
    {
        if (empty($employee) || $employee->role_id != 2) {
            return response()->json([
                'status' => false,
                'message' => "Employee not found",
            ], 404);
        }

        try {
            DB::beginTransaction();
            $inputs = $request->except(
                'image',
            );
            if (!empty($request->image)) {
                if (!empty($employee->image) && file_exists(public_path('storage/' . $employee->image)))
                    unlink(public_path('storage/' . $employee->image));
                $image = $request->image;
                $filename = "Profile-Photo" . time() . "-" . rand() . "." . $image->getClientOriginalExtension();
                $image->storeAs('employee', $filename, "public");
                $inputs['image'] = "employee/" . $filename;
            }
            $employee->update($inputs);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Employee has been successfully updated",
                'employee' => new AllEmployeeResource($employee),
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
     * @param  \App\Models\User $employee
     */
    public function destroy(User $employee)
    {
        if (empty($employee) || $employee->role_id != 2) {
            return response()->json([
                'status' => false,
                'message' => "Employee not found",
            ], 404);
        }

        try {
            DB::beginTransaction();
            if (!empty($employee->image) && file_exists(public_path('storage/' . $employee->image)))
                unlink(public_path('storage/' . $employee->image));
            $employee->delete();
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Employee has been successfully deleted",
            ]);
        } catch (Throwable $th) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function status_change($id)
    {
        try {
            DB::beginTransaction();
            $employee = User::where('id', $id)->first();
            if (empty($employee))
                throw new Error('User not found');
            if ($employee->is_active == 1) {
                $employee->is_active = 0;
                if (!$employee->save())
                    throw new Error('Account Status not change');
                DB::commit();
                return response()->json(['status' => true, 'message' => 'Successfully Account Status Change to De-activate']);
            } else {
                $employee->is_active = 1;
                if (!$employee->save())
                    throw new Error('Account Status not change');
                DB::commit();
                return response()->json(['status' => true, 'message' => 'Successfully Account Status Change to Activate']);
            }
        } catch (Throwable $th) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $th->getMessage()], 500);
        }
    }
}
