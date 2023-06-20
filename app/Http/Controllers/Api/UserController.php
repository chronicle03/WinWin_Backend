<?php

namespace App\Http\Controllers\Api;


use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use Laravel\Passport\HasApiTokens;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Rules\Password;
use Illuminate\Contracts\Auth\MustVerifyEmail;


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

        if ($validator->fails()) {
            //jika gagal
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'birthdate' => $request->birthdate,
            'phone_number'=> $request->phone_number,
            'password' => Hash::make($request->password),
        ]);

        $user->sendEmailVerificationNotification();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil register. Silahkan cek email anda untuk melakukan verifikasi'
        ],200);
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => ['required_without_all:email,phone_number', 'string'],
                'email' => ['required_without_all:username,phone_number', 'string', 'email'],
                'phone_number' => ['required_without_all:username,email', 'string'],
                'password' => ['required', 'string'],
            
            ],[
                'username.required_without_all' => 'The username field must be filled when the username or telephone number does not exist.',
                'email.required_without_all' => 'The email field must be filled when the username or telephone number does not exist.',
                'phone_number.required_without_all' => 'The phone number field must be filled in when the username or telephone number does not exist.',
                'password.required' => 'Password field is required', 
            ]);

            if ($validator->fails()) {
                return ResponseFormatter::error([
                    'message' => 'Bad Request',
                    'errors' => $validator->errors()
                ], 'Bad Request', 400);
            }

            $username = $request->input('username');
            $email = $request->input('email');
            $phone_number = $request->input('phone_number');
            $password = $request->input('password');

            $user = null;

            if (!empty($username)) {
                $user = User::where('username', $username)->first();

                if (!$user) {
                    return ResponseFormatter::error([
                        'message' => 'User not found',
                    ], 'Authentication failed', 404);
                }
            } elseif (!empty($email)) {
                $user = User::where('email', $email)->first();

                if (!$user) {
                    return ResponseFormatter::error([
                        'message' => 'User not found',
                    ], 'Authentication failed', 404);
                }
            } elseif (!empty($phone_number)) {
                $user = User::where('phone_number', $phone_number)->first();

                if (!$user) {
                    return ResponseFormatter::error([
                        'message' => 'User not found',
                    ], 'Authentication failed', 404);
                }
            }

            if (!Hash::check($password, $user->password)) {
                return ResponseFormatter::error([
                    'message' => 'Oops! The password you entered is incorrect.',
                ], 'Authentication failed', 401);
            }

            if (!$user->hasVerifiedEmail()) {
                return ResponseFormatter::error([
                    'message' => 'Email verification required. Please check your email for verification instructions.',
                ], 'Authentication failed', 401);
            }

            $token = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $token,
                'token_type' => env('TOKEN_TYPE', 'Bearer'),
                'user' => $user
            ], "Congratulations, you have successfully logged in!");
        } catch (Exception $error) {
            return ResponseFormatter::error([
                "message" => "Something error",
                "error" => $error
            ], 'Authentication failed', 500);
        }
    }
     
    // Metode lainnya...

    public function getAllUsers()
    {
        try {
            $users = User::all();

            return ResponseFormatter::success([
                'users' => $users
            ], "Success get all users");
        } catch (Exception $error) {
            return ResponseFormatter::error([
                "message" => "Something error",
                "error" => $error
            ], 'Authentication failed', 500);
        }
    }

    public function getUserById($id)
    {
        try {
            $user = User::find($id);

            if ($user) {
                return ResponseFormatter::success([
                    'user' => $user
                ], "Success get user");
            }

            return ResponseFormatter::error([
                'message' => 'User not found',
            ], 'User not found', 404);
        } catch (Exception $error) {
            return ResponseFormatter::error([
                "message" => "Something error",
                "error" => $error
            ], 'Authentication failed', 500);
        }
    }
}

