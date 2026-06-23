<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Models\User;
use App\Models\UserWallet;
use JWTAuth, Validator;

class AuthController extends Controller
{
    /*
    * Logins in a user
    *
    * @param $request
    */
    public function loginUser(Request $request)
    {
        $validation = Validator::make($request->all(),
            [

                "password" => "required",
                "email" =>"required|email",
                "user_type"=>"required"

            ]);

        if($validation->fails()){
            return $validation->errors();
        }
        $credentials = ['email' => $request['email'], 'password' => $request['password'], 'status' => 'active', 'user_type'=>'user'];

        $token = JWTAuth::attempt($credentials);

        if (!$token) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Invalid user details',
                ], 401);
        }
       // UserController::updateLastLogin(JWTAuth::user()->id);
        $user = JWTAuth::user();
        return response()->json([
            'success' => true,
            'status' => 'success',
            'token' => $token,
            'message' => 'Login Succesful',
            'user' => JWTAuth::user()->load(['userwallet']),
            ]);

    }


     /*
    * Logins in a user
    *
    * @param $request
    */
    public function loginRider(Request $request)
    {
        $validation = Validator::make($request->all(),
            [

                "password" => "required",
                "email" =>"required|email",
                "user_type"=>"required"

            ]);

        if($validation->fails()){
            return $validation->errors();
        }
        $credentials = ['email' => $request['email'], 'password' => $request['password'], 'status' => 'active', 'user_type'=>'rider'];

        $token = JWTAuth::attempt($credentials);

        if (!$token) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Invalid Rider details',
                ], 401);
        }
       // UserController::updateLastLogin(JWTAuth::user()->id);
        $user = JWTAuth::user();
        return response()->json([
            'success' => true,
            'status' => 'success',
            'token' => $token,
            'message' => 'Login Succesful',
            'user' => JWTAuth::user()->load(['userwallet']),
            ]);

    }


    /**
     * Logs a user in at registration point
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */

    public static function loginAtReg(Request $request){

        $credentials = ['email' => $request['email'], 'password' => $request['password'], 'status' => 'active'];
        $token = JWTAuth::attempt($credentials);

       // UserController::updateLastLogin(JWTAuth::user()->id);
        return [

            'token' => $token,
            'user' => JWTAuth::user()->load(['userwallet']),
        ];

    }


    public function logout()
    {
        JWTAuth::logout();
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }

    public function me()
    {
        return response()->json([
            'success' => true,
            'data' => JWTAuth::user()->load(['userwallet', 'userProfile', 'riderProfile']),
        ]);
    }

    public function profile()
    {
        return $this->me();
    }
    
 //

    /**
     * FORGOT PASSWORD — request a password reset token
     * POST /api/auth/forgot-password
     */
    public function forgotPassword(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validation->errors(),
            ], 422);
        }

        Password::sendResetLink($request->only('email'));

        return response()->json([
            'success' => true,
            'message' => 'If an account exists for this email, a reset code has been sent.',
        ]);
    }

    /**
     * RESET PASSWORD — reset a user's password using a token
     * POST /api/auth/reset-password
     */
    public function resetPassword(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validation->errors(),
            ], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password updated. You can now log in.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __($status),
        ], 422);
    }

    public function auth(Request $request)
                                     {
    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json(['access_token' => $token, 'token_type' => 'Bearer']);
    }


}
