<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function signup_process(Request $request)
    {
        $valid = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'username' => 'required|unique:users,username',
            'phone' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'password' => 'required|min:3',
        ]);
        if ($valid->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation errors', 'errors' => $valid->errors()], 422);
        }
        try {
            DB::beginTransaction();
            $user = new User();
            $user->role_id = 2;
            $user->first_name =  $request->first_name;
            $user->last_name =  $request->last_name;
            $user->username =  $request->username;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->phone = $request->phone;
            $user->address = $request->address;
            if (!empty($request->image)) {
                $image = $request->image;
                $filename = "Image-" . time() . "-" . rand() . "." . $image->getClientOriginalExtension();
                $image->storeAs('image', $filename, "public");
                $user->image = "image/" . $filename;
            }
            if (!$user->save()) throw new Error("User not Added!");
            DB::commit();
            $client = User::with('role')->where('id', $user->id)->get();
            return response()->json(['status' => true, 'message' => "User Successfully Added", 'user' => $client], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => "User not Added"], 500);
        }
    }

    public function login_process(Request $request)
    {
        $valid = Validator::make($request->all(), [
            'email' => 'required|exists:users,email',
            'password' => 'required|min:3',
        ]);

        if ($valid->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation errors', 'errors' => $valid->errors()], 422);
        }

        if (auth()->attempt([
            'email' => $request->email,
            'password' => $request->password,
            'role_id' => 1,
        ])) {
            $user = auth()->user();
            $token = $user->createToken('token')->accessToken;
            return response()->json(['status' => true, 'message' => 'Successfully Login', 'token' => $token, 'user' => $user], 200);
        } elseif (auth()->attempt([
            'username' => $request->email,
            'password' => $request->password,
            'role_id' => 2,
        ])) {
            $user = auth()->user();
            $token = $user->createToken('token')->accessToken;
            return response()->json(['status' => true, 'message' => 'Successfully Login', 'token' => $token, 'user' => $user], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'Invalid Credentials'], 500);
        }
    }

    public function forgot_process(Request $request)
    {
        $valid = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);
        if ($valid->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation errors', 'errors' => $valid->errors()], 422);
        }
        $user = User::where('email', $request->email)->first();
        if (!empty($user)) {
            $user->token = rand(1, 10000);
            $user->save();
            $email = $request->email;
            $token = $user->token;
            Mail::send('admin.mail.forgotPassword',  compact('email', 'token'), function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Reset Password');
            });
            return response()->json(['status' => true, 'message' => "Reset Email send to {$email}", 'token' => $token, 'user' => $user,], 200);
        } else {
            return response()->json(['status' => false, 'message' => "User not found"], 404);
        }
    }

    public function reset_password_process(Request $request)
    {
        $valid = Validator::make($request->all(), [
            'email' => 'required',
            'token' => 'required',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);
        if ($valid->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation errors', 'errors' => $valid->errors()], 422);
        }
        $user = User::where('email', $request->email)->where('token', $request->token)->first();
        if (empty($user)) return response()->json(['status' => false,'message' => "User not found"], 404);
        if (Hash::check($request->password, $user->password)) return response()->json(['status' => false, 'message', 'Please use different from current password.'], 500);
        $user->password = Hash::make($request->password);
        $user->token = null;
        $user->save();
        return response()->json(['status' => true, 'message' => "Password reset succesfully", 'user' => $user], 200);
    }

    public function edit_profile($id)
    {
        if (empty($id)) return response()->json(['message', 'id not found'], 404);
        $client = User::where('id', $id)->first();
        if (!empty($client)) return response()->json(['status' => true, 'message' => "User found", 'user' => $client ?? []], 200);
        else return response()->json(['status' => false, 'message', 'User not found'], 404);
    }

    public function update_profile(Request $request)
    {
        $client = User::where('id', auth()->user()->id)->first();
        if (!empty($client)) {
            $valid = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email,' . auth()->user()->id,
                'username' => 'required|unique:users,username,' . auth()->user()->id,
                'phone' => 'required',
                'fname' => 'required',
                'lname' => 'required',
                'address' => 'required',
            ]);
            if ($valid->fails()) {
                return response()->json(['status' => false, 'message' => 'Validation errors', 'errors' => $valid->errors()], 422);
            }
            try {
                DB::beginTransaction();
                $client->role_id = 2;
                $client->first_name =  $request->first_name;
                $client->last_name =  $request->last_name;
                $client->username =  $request->username;
                $client->email = $request->email;
                $client->password = Hash::make($request->password);
                $client->phone = $request->phone;
                $client->address = $request->address;
                if (!empty($request->image)) {
                    $image = $request->image;
                    $filename = "Image-" . time() . "-" . rand() . "." . $image->getClientOriginalExtension();
                    $image->storeAs('image', $filename, "public");
                    $client->image = "image/" . $filename;
                }
                if (!$client->save()) throw new Error("User not Added!");
                DB::commit();
                $client = User::with('role')->where('id', $client->id)->get();
                return response()->json(['status' => true, 'message' => "User Successfully Updated", 'client' => $client,], 200);
            } catch (\Throwable $th) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => "User not Update"], 500);
            }
        } else return response()->json(['status' => false, 'message', 'User not found'], 404);
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
                'admin.contact',
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
