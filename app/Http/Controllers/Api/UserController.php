<?php

namespace App\Http\Controllers\Api;


use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Models\Ability;
use App\Models\Skill;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\HasApiTokens;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Rules\Password;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\DB;


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

            if ($request->confirm_password != $request->password) {
                return ResponseFormatter::error([
                    'message' => 'Bad Request',
                    'errors' => $validator->errors()->add('confirm_password', 'confirmation password must be the same as the password'),
                ], 'Bad Request', 400);
            }

            if ($request->is_checked == "false") {
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
                'phone_number' => $request->phone_number,
                'password' => Hash::make($request->password),
            ])->sendEmailVerificationNotification();

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

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => ['required_without_all:email,phone_number', 'string'],
                'email' => ['required_without_all:username,phone_number', 'string', 'email'],
                'phone_number' => ['required_without_all:username,email', 'string'],
                'password' => ['required', 'string'],

            ], [
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
                        'message' => 'not found',
                        'errors' => 'user not found'
                    ], 'Authentication failed', 404);
                }
            } elseif (!empty($email)) {
                $user = User::where('email', $email)->first();

                if (!$user) {
                    return ResponseFormatter::error([
                        'message' => 'not found',
                        'errors' => 'user not found'
                    ], 'Authentication failed', 404);
                }
            } elseif (!empty($phone_number)) {
                $user = User::where('phone_number', $phone_number)->first();

                if (!$user) {
                    return ResponseFormatter::error([
                        'message' => 'not found',
                        'errors' => 'user not found'
                    ], 'Authentication failed', 404);
                }
            }

            if (!Hash::check($password, $user->password)) {
                return ResponseFormatter::error([
                    'message' => 'Oops! The password you entered is incorrect.',
                    'errors' => 'Oops! The password you entered is incorrect.'
                ], 'Authentication failed', 401);
            }

            if (!$user->hasVerifiedEmail()) {
                return ResponseFormatter::error([
                    'message' => 'Email verification required. Please check your email for verification instructions.',
                    'errors' => 'Email verification required. Please check your email for verification instructions.'
                ], 'Authentication failed', 401);
            }


            $token = $user->createToken('authToken')->plainTextToken;

            $userSkills = $user->ability->pluck('skills')->toArray();

            return ResponseFormatter::success([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ], "Congratulations, you have successfully logged in!");
        } catch (Exception $error) {
            return ResponseFormatter::error([
                "message" => "Something error",
                "error" => $error
            ], 'Authentication failed', 500);
        }
    }

    Public function updateProfile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['string', 'max:100'],
                'birthdate' => ['string'],
                'location' => ['string'],
                'gender' => ['required', 'string',],
                'phone_number' => ['numeric', 'unique:users'],
                'email' => ['string', 'max:255', 'email', 'unique:users'],
                'job_status' => ['string'],
                'skills' => ['array'],
                'bio' => ['string'],
                'profile_photo_path' => ['image'],
            ]);

            if ($validator->fails()) {
                return ResponseFormatter::error([
                    'message' => 'Bad Request',
                    'errors' => $validator->errors()
                ], 'Bad Request', 400);
            }

            $data = $request->except('skills');
            $user = Auth::user();

            $skills = $request->skills;
            if (!empty($skills)) {
                foreach ($skills as $skillName) {
                    $skill = Skill::where('name', $skillName)->first();
                    if (empty($skill)) {
                        Skill::create([
                            'name' => $skillName,
                        ]);
                    }

                    $skill = Skill::where('name', $skillName)->first();

                    $ability = Ability::where('user_id', $user->id)->where('skills_id', $skill->id)->first();

                    if (empty($ability)) {
                        Ability::create([
                            'user_id' => $user->id,
                            'skills_id' => $skill->id,
                        ]);
                    }
                }
            }

            if ($request->profile_photo_path) {

                $image = $request->file('profile_photo_path');
                $file_name = $user->id . $user->name . '.' . $image->getClientOriginalExtension();
                try {
                    $storage_image = $request->file('profile_photo_path')->storeAs('public/images', $file_name);
                } catch (\Exception $e) {
                    return ResponseFormatter::error([
                        'message' => 'something error',
                        'errors' => $e
                    ], 'authentication failed', 500);
                }
                $user->profile_photo_path = Storage::url('images/' . $file_name);
            }


            $user->update($data);

            $userSkills = $user->ability->pluck('skills')->toArray();
            // $user['skills'] = $userSkills;


            return ResponseFormatter::success([
                'user' => $user,
            ], "updated profile success");
        } catch (Exception $e) {
            return ResponseFormatter::error([
                'message' => 'something error',
                'errors' => $e
            ], 'authentication failed', 500);
        }
    }


    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken()->delete();
        return ResponseFormatter::success($token, "token revoked");
    }

    public function verify($id, Request $request)
    {
        if (!$request->hasValidSignature()) {
            return response()->json([
                'status' => false,
                'message' => 'Verifikasi email gagal.'
            ], 400);
        }
        $user = User::find($id);

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return  redirect()->to('/');
    }

    public function notice()
    {
        return response()->json([
            'status' => false,
            'message' => 'Anda belum melakukan verifikasi email.'
        ], 400);
    }

    public function resend()
    {
        if (Auth::user()->hasVerifiedEmail()) {
            return response()->json([
                'status' => true,
                'message' => 'Email sudah diverifikasi'
            ], 200);
        }

        Auth::user()->sendEmailVerificationNotification();
        return response()->json([
            'status' => true,
            'message' => 'Link verifikasi email sudah dikirim ke email anda.'
        ], 200);
    }


    public function getAllUsers()
    {
        try {
            $users = User::with('ability.skills')->get();

            $userSkills = [];
            foreach ($users as $user) {
                $skills = $user->ability->pluck('skills');
                $userSkills[] = $skills;
            }
            return ResponseFormatter::success([
                'users' => $users
            ], 'Success get all users');
        } catch (\Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage()
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
