<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\Rules\Password;


class UserController extends Controller
{  
    public function register(Request $request)
    {
        //validasi
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100', 'unique:users'],
            'email' => ['required', 'string', 'max:255', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'phone_number' => ['required', 'numeric', 'unique:users'],
            'birthdate' => ['date'],
            'bio' => ['string', 'max:500'],
            'location' => ['string'],
            'job_status' => ['string'],
        ],[
            'name.required' => 'Nama harus diisi.',
            'username.unique' => 'Username sudah dipakai.',
            'username.required' => 'Username harus diisi.',
            'email.required' => 'Email harus diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password harus diisi.',
            'password.confirmed' => 'Password tidak sama.',
            'phone_number.required' => 'Nomor telepon harus diisi.',
            'phone_number.unique' => 'Nomor telepon sudah diisi. ',
        ]);

        if ($validator->fails()){
            //jika gagal
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 400);
        }else{
            //jika ok, simpan user baru
            $user = new User();
            $user->name = $request->name;
            $user->username = $request->username;
            $user->birthdate = $request->birthdate;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->phone_number = $request->phone_number;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'User registered.'
            ],201);
        }
    }

    // Metode lain di sini...
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => ['required', 'string'],
                'email' => ['required', 'string', 'email'],
                'phone_number' => ['required', 'string'],
                'password' => ['required', 'string'],
            ]);
    
            if ($validator->fails()) {
                return ResponseFormatter::error([
                    'message' => 'Bad Request',
                    'errors' => $validator->errors()
                ], 'Bad Request', 400);
            }
    
            $user = User::where(function ($query) use ($request) {
            $query->where('username', $request->input('username'))
                    ->orWhere('email', $request->input('email'))
                    ->orWhere('phone_number', $request->input('phone_number'));
    })->first();
    
            if (!$user) {
                return ResponseFormatter::error([
                    'message' => 'User not found',
                    ], 'Authentication failed', 404);
            }
    
            if (!Hash::check($request->input('password'), $user->password)) {
                return ResponseFormatter::error([
                    'message' => 'Invalid password',
                ], 'Authentication failed', 401);
            }
    
            $token = $user->createToken('authToken')->plainTextToken;
    
            return ResponseFormatter::success([
                'access_token' => $token,
                'token_type' => env('TOKEN_TYPE', 'Bearer'),
                'user' => $user
            ], "Login successful");
        } catch (Exception $error) {
            return ResponseFormatter::error([
                "message" => "Something error",
                "error" => $error
            ], 'Authentication failed', 500);
        }
    }
}
