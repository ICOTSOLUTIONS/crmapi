<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ProfileUpdateRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\Auth\LoginResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AuthController extends Controller
{
    public function currentUser()
    {
        try {
            $user = User::find(auth()->user()->id);
            if (empty($user))
                throw new Error('User not found');
            return response()->json(['status' => true, 'message' => 'User found', 'user' => new UserResource($user->load('role'))]);
        } catch (Throwable $th) {
            return response()->json(['status' => false, 'message' => $th->getMessage()], 500);
        }
    }
    
    public function login(LoginRequest $request)
    {
        try {
            if (auth()->attempt($request->only('email', 'password'))) {
                $user = auth()->user()->load('role');
                if ($user->is_active == false)
                    return response()->json(['status' => false, 'message' => 'Your Account Status has been not Active, Please Contact with admin']);
                return new LoginResource(['token' => $user->createToken($user->email)->accessToken, 'user' => $user]);
            }
            throw new Error('Invalid Credentials', 412);
        } catch (Throwable $th) {
            return response()->json(['status' => false, 'message' => $th->getMessage()], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate(['email' => 'required|email|exists:users,email']);
            $user = User::where('email', $request->email)->first();
            $otp = rand(100000, 999999);
            $token = DB::select("SELECT * FROM password_reset_tokens WHERE email = ?", [$user->email]);
            if (isset($token[0])) {
                DB::update('update password_reset_tokens set token = ? where email = ?', [$otp, $user->email]);
            } else {
                DB::insert("insert into password_reset_tokens (email, token) values (?, ?)", [$user->email, $otp]);
            }

            Mail::send('mail.reset-password', ['otp' => $otp, 'email' => $user->email], function ($message) use ($user) {
                $message->to($user->email, $user->name);
                $message->subject('Reset Password - OTP');
                $message->priority(3);
            });
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "OTP has been to " . $user->email,
            ]);
        } catch (Throwable $th) {
            DB::rollback();
            return response()->json(['status' => false, 'message' => $th->getMessage()], 500);
        }
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            DB::beginTransaction();
            User::where('email', $request->email)->update(['password' => Hash::make($request->password)]);
            DB::delete('DELETE FROM password_reset_tokens WHERE email = ?', [$request->email]);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Password has been reset for " . $request->email,
            ]);
        } catch (Throwable $th) {
            DB::rollback();
            return response()->json(['status' => false, 'message' => $th->getMessage()], 500);
        }
    }

    public function profileUpdate(ProfileUpdateRequest $request)
    {
        try {
            DB::beginTransaction();
            $user = User::where('id', auth()->user()->id)->first();
            if (empty($user))
                throw new Error('admin not found');
            $inputs = $request->except(
                'image',
            );
            if (!empty($request->image)) {
                if (!empty($user->image) && file_exists(public_path('storage/' . $user->image)))
                    unlink(public_path('storage/' . $user->image));
                $image = $request->image;
                $filename = "Profile-Photo" . time() . "-" . rand() . "." . $image->getClientOriginalExtension();
                $image->storeAs('profile', $filename, "public");
                $inputs['image'] = "profile/" . $filename;
            }
            $user->update($inputs);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Profile has been Successfully Updated.",
                'user' => new UserResource($user->load('role')),
            ]);
        } catch (Throwable $th) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            DB::beginTransaction();
            $user = User::where('id', auth()->user()->id)->first();
            if (empty($user))
                throw new Error('user not found');
            $inputs = $request->except(
                'password',
            );
            $inputs['password'] = Hash::make($request->password);
            $user->update($inputs);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Password Change Successfully.",
            ]);
        } catch (Throwable $th) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function logout()
    {
        auth()->logout();
        return response()->json(['status' => true, 'message' => "Logout Successfully"], 200);
    }

    public function contact(Request $request)
    {
        $request->validate([]);
        $email = 'icotsolutions@gmail.com';
        $data = [
            'phone' => $request->phone,
            'email' => $request->email,
            'name' => $request->name,
            'Message' => $request->Message,
        ];

        try {
            Mail::send(
                'mail.contact',
                ['data' => $data],
                function ($message) use ($email) {
                    $message->to($email);
                    $message->subject('Contact');
                }
            );
            return response()->json(['status' => true, 'Message' => 'Contact Successfully'], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'Message' => $th->getMessage()], 500);
        }
    }
}
