<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\Rule;
use App\Mail\SendOtpMail;
use Illuminate\Support\Facades\Mail;

class AppController extends Controller
{

     public function generateOTP($length = 5) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $otp;
    }

      public function register(Request $request)
    {
      $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
    //           'email' => [
    //     'required',
    //     'email',
    //     Rule::unique('users')->where(function ($query) {
    //         return $query->where('origin', 'app');
    //     })
    // ],
    'phone' => 'required|unique:users,phone',
    //        'phone' => [
    //     'required',
    //     Rule::unique('users')->where(function ($query) {
    //         return $query->where('origin', 'app');
    //     }),
    // ],
           'password' => [
    'required',
    'string',
    'min:8',
    'confirmed',
    'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
],
        ]);

        if ($validator->fails()) {
    return response()->json([
        'status' => 0,
        'errors' => $validator->errors(),
    ], 422);
}

        $otp = $this->generateOTP();

        Mail::to($request->email)->send(new SendOtpMail($otp));

        // $user = User::create([
        //     'name' => $request->name,
        //     'email' => $request->email,
        //     'origin' => 'app',
        //     'phone' => $request->phone,
        //     'password' => Hash::make($request->password),
        // ]);

        // $token = JWTAuth::fromUser($user);

        return response()->json([
            // 'message' => 'User registered successfully',
            'status' => 1,
            'otp' => $otp
            // 'user' => $user,
            // 'token' => $token
        ], 201);
    }

    public function save_register_data(Request $request)
    {

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'origin' => 'app',
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'User registered successfully',
            'status' => 1,
            'token' => $token,
            'user' => $user
        ], 201);
    }

     public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['status' => 0, 'message' => 'Incorrect Email'], 401);
        }
        if (Hash::check($request->password, $user->password)) {
             $otp = $this->generateOTP();
              Mail::to($request->email)->send(new SendOtpMail($otp));   
               return response()->json([
            'status' => 1,
            'otp' => $otp
        ], 201);
        } else {
            return response()->json(['status' => 0, 'message' => 'Incorrect Password'], 401);
        }
    }

    public function token_for_login(Request $request)
    {
        $user = User::where("email", $request->email)->first();
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'User login successfully',
            'status' => 1,
            'token' => $token,
            'user' => $user
        ], 201);
    }
}
