<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\Rule;

class AppController extends Controller
{
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
            return response()->json($validator->errors(), 422);
        }

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
            'user' => $user,
            'token' => $token
        ], 201);
    }
}
