<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use Exception;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Rules\Password;


class UserController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:100'],
                'username' => ['required', 'string', 'max:100', 'unique:users'],
                'email' => ['required', 'string', 'max:255', 'email', 'unique:users'],
                'password' => ['required', 'string', new Password],
                'confirm_password' => ['required'],
                'birthdate' => ['string'],
                'is_checked' => ['required'],
                'phone_number' => ['required', 'numeric', 'unique:users'],
            ]);
    
            if ($validator->fails()) {
                return ResponseFormatter::error([
                    'message' => 'Bad Request',
                    'errors' => $validator->errors()
                ], 'Bad Request', 400);
            }

            if ($request->confirm_password != $request->password){
                return ResponseFormatter::error([
                    'message' => 'Bad Request',
                    'errors' => $validator->errors()->add('confirm_password', 'confirm password must be the same as the password'),
                ], 'Bad Request', 400);
            }

            if ($request->is_checked == "false"){
                return ResponseFormatter::error([
                    'message' => 'Bad Request',
                    'errors' => $validator->errors()->add('is_checked', 'terms of service & privacy policy must be agree'),
                ], 'Bad Request', 400);
            }

            User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'birthdate' => $request->birthdate,
                'phone_number'=> $request->phone_number,
                'password' => Hash::make($request->password),
            ]);

            $user = User::where('email', $request->email)->first();

            $token = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $token,
                'token_type' => env('TOKEN_TYPE', 'secret'),
                'user' => $user
            ], "user registered");
        } catch (Exception $error) {
            return ResponseFormatter::error([
                "message" => " something erorr",
                "error" => $error
            ], 'authentication failed', 500);
        }
    }

    public function getAllUsers()
    {   
        try {
            $user = User::all();
            
            return ResponseFormatter::success([
                'user' => $user
            ], "success get all users");
        } catch (Exception $error) {
            return ResponseFormatter::error([
                "message" => " something erorr",
                "error" => $error
            ], 'authentication failed', 500);
        }
    }

    public function getUserById($id)
    {   
        try {
            $user = User::find($id);
            
            if ($user) {
                return ResponseFormatter::success([
                    'user' => $user
                ], "success get user");
            }

            return ResponseFormatter::error([
                'message' => 'not found',
            ], 'user not found', 404);
            
            
        } catch (Exception $error) {
            return ResponseFormatter::error([
                "message" => " something erorr",
                "error" => $error
            ], 'authentication failed', 500);
        }
    }

}
