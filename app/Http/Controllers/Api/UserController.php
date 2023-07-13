<?php

namespace App\Http\Controllers\Api;


use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\PasswordReset;
use App\Models\Favorite;
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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
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
                'password' => ['required', 'string', 'min:8', new Password],
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
                'token_type' => "Bearer",
                'user' => $user
            ], "user registered");
        } catch (Exception $error) {
            return ResponseFormatter::error([
                "message" => " something erorr",
                "errors" => $error
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
                        'message' => 'User not found',
                        'errors' => 'User not found'
                    ], 'Authentication failed', 404);
                }
            } elseif (!empty($email)) {
                $user = User::where('email', $email)->first();

                if (!$user) {
                    return ResponseFormatter::error([
                        'message' => 'User not found',
                        'errors' => 'Email Unregistered'
                    ], 'Authentication failed', 404);
                }
            } elseif (!empty($phone_number)) {
                $user = User::where('phone_number', $phone_number)->first();

                if (!$user) {
                    return ResponseFormatter::error([
                        'message' => 'User not found',
                        'errors' => 'User not found'
                    ], 'Authentication failed', 404);
                }
            } 

            if ($user->email_verified_at == null) {
                return ResponseFormatter::error([
                    'message' => 'Oops! Your email has not been verified',
                    'errors' => 'Oops! Your email has not been verified'
                ], 'Authentication failed', 400);
            }

            if (!Hash::check($password, $user->password)) {
                return ResponseFormatter::error([
                    'message' => 'Oops! The password you entered is incorrect.',
                    'errors' => 'Oops! The password you entered is incorrect.'
                ], 'Authentication failed', 401);
            }

            $token = $user->createToken('authToken')->plainTextToken;
            $userSkills = $user->ability->pluck('skills')->toArray();
            $userFavorites = $user->favorite;

            return ResponseFormatter::success([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ], "Congratulations, you have successfully logged in!");
        } catch (Exception $error) {
            return ResponseFormatter::error([
                "message" => "Something error",
                "errors" => $error
            ], 'Authentication failed', 500);
        }
    }

    public function updateProfile(Request $request)
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
                'skills' => ['string'],
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

            $skillsString = $request->input('skills'); // Ambil nilai string dari request

            if (!empty($skillsString)) {
                $skills = explode(',', $skillsString);

                Ability::where('user_id', $user->id)->delete();

                foreach ($skills as $skillName) {
                    $skill = Skill::firstOrCreate(['name' => $skillName]);

                    Ability::create([
                        'user_id' => $user->id,
                        'skills_id' => $skill->id,
                    ]);
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

            $user->load('ability.skills', 'favorite');
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

        return  view('verifyemail');
    }

    public function notice()
    {
        return response()->json([
            'status' => false,
            'message' => 'Anda belum melakukan verifikasi email.'
        ], 400);
    }

    public function resend(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return ResponseFormatter::error([
                "message" => "something erorr",
                "error" => "Email not found"
            ], 'not found', 404);
        }

        if ($user->hasVerifiedEmail()) {
            return ResponseFormatter::error([
                "message" => "something erorr",
                "error" => "Your email has been verified"
            ], 'not found', 404);
        }

        $user->sendEmailVerificationNotification();
        return ResponseFormatter::success($user->email, "Verification email has been sent");
    }

    public function forgetPassword(Request $request)
    {
        try {

            $user = User::where('email', $request->email)->get();
            if (count($user) > 0) {

                $token = Str::random();
                $domain = URL::to('/');
                $url = $domain . '/reset-password?token=' . $token;

                $data['url'] = $url;
                $data['email'] = $request->email;
                $data['title'] = "Password Reset";
                $data['body'] = "Please click on below link to reset your password.";

                Mail::send('forgetPasswordMail', ['data' => $data], function ($message) use ($data) {
                    $message->to($data['email'])->subject($data['title']);
                });

                $datetime = Carbon::now()->format('Y-m-d H:i:s');
                PasswordReset::updateOrCreate(
                    ['email' => $request->email],
                    [
                        'email' => $request->email,
                        'token' => $token,
                        'created_at' => $datetime
                    ]
                );

                return ResponseFormatter::success([
                    "message" => "Please check your mail to reset your password",

                ], 'Please check your mail to reset your password.');
                //response()->json(['status'=>true, 'message'=>'Please check your mail to reset your password']);

            } else {
                return response()->json(['status' => false, 'message' => 'User not found.']);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    //fungsi reset password view Load
    public function resetPasswordLoad(Request $request)
    {
        $resetData = PasswordReset::where('token', $request->token)->get();
        if (isset($request->token) && count($resetData) > 0) {

            $user = User::where('email', $resetData[0]['email'])->get();
            return view('resetPassword', compact('user'));
        } else {
            return view('404');
        }
    }

    //Fungsi Reset Password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed'
        ]);

        $user = User::find($request->id);
        $user->password = Hash::make($request->password);
        $user->save();

        PasswordReset::where('email', $user->email)->delete();

        return view('successResetPassword');
    }


    public function getAllUsers()
    {
        try {
            $users = User::with('ability.skills', 'favorite')->get();

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

    public function getFavorites(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error([
                'message' => 'Bad Request',
                'errors' => $validator->errors()
            ], 'Bad Request', 400);
        }

        $favorites = Favorite::where('user_id', $request->user_id)->get();
        

        return ResponseFormatter::success([
            'favorites' => $favorites
        ], "Success get favorites");
    }

    public function createFavorite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'exists:users,id'],
            'user_favorite_id' => ['required', 'exists:users,id'],
        ]);
    
        if ($validator->fails()) {
            return ResponseFormatter::error([
                'message' => 'Bad Request',
                'errors' => $validator->errors()
            ], 'Bad Request', 400);
        }
    
        $favorite = Favorite::updateOrCreate(
            ['user_id' => $request->user_id, 'user_favorite_id' => $request->user_favorite_id],
            $request->all()
        );
    
        $users = Auth::user();
        $users->load('ability.skills', 'favorite');
    
        return ResponseFormatter::success([
            'users' => $users
        ], "Favorite created");
    }
}
