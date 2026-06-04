<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
                'status' => 'error',
                'message' => 'Invalid user details',
                ]);
        }
       // UserController::updateLastLogin(JWTAuth::user()->id);
        $user = JWTAuth::user();
        return response()->json([
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
                'status' => 'error',
                'message' => 'Invalid Rider details',
                ]);
        }
       // UserController::updateLastLogin(JWTAuth::user()->id);
        $user = JWTAuth::user();
        return response()->json([
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
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }
    
 //

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
