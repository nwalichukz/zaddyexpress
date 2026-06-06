<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Wallet\UserWalletController;
use App\Http\Controllers\Funding\BillStackController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Email\Mailer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use DB, Auth, Validator, Response, JWTAuth, Str;
use Carbon\Carbon;

class UserController extends Controller
{
    /**
     * saves a users password and email instance
     *
     * @return bool
     */
    public static function save($request)
    {
        $save = new User;
        $save->name = $request['name'];
        $save->email = $request['email'];
        $save->password = Hash::make($request['password']);
        $save->status = 'active';
        $save->is_verified = 'yes';
        $save->user_type = $request['user_type'];
        $save->save();
        // event(new UserSignUp($save));
        // \Event::dispatch(new UserSignUp($save));
        return $save->id;
    }



    /**
     * Store a newly created resource in db.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     **/
    public function create(Request $request)
    {    $validation = Validator::make($request->all(),
        [
            "password" => "required|min:8",
            "name" => "required|min:3",
             "mobile_number" => "required|digits:11",
            "email" =>"required|unique:users|email:rfc,dns",
        ]);

    if($validation->fails()){
        return response()->json([
            'status' => 403,
            'message' => $validation->errors(),
        ]);
    }

    try{
        DB::transaction(function() use ($request){


                $result = self::save($request);
                SettingController::save($result);
                UserWalletController::save($result);
                

          });
         //BillStackController::createStaticVirtualAccountEvent($request);

        $login = AuthController::loginAtReg($request);

        return response()->json([
            'status' =>'success',
            'message' => 'User created successfully.',
            'token' => $login['token'],
            'user' => $login['user']
        ]);


    }catch(Exception $e){
            return Response()->json([
                'status' =>'error',
                'message' => 'Something went wrong. User not created successfully'
             ]);

        }
        return Response()->json([
            'status' =>'error',
            'message' => 'Something went wrong, user not created successfully. Please try again'
         ]);

        //
    }

    /**
     * checks referral code
     *
     * if it exists
     *
     *
     */
    public static function checkReferralCode($referral_code){
       $user = User::where('referral_code', $referral_code)->first();
       if(!empty($user->id)){
           return true;
       }else{
           return false;
       }
    }

  

     /**
     * This method retrieves
     *
     *  user(s)
     *
     * @param $id
     */
    public static function get($id = null){
        if(!empty($id)){
            return $data = User::where(['id' => $id])->with(['userwallet'])->first();
        }elseif(empty($id)){
              return $data = User::whereNotNull('id')->where('status', 'active')->with(['userwallet'])->orderBy('created_at', 'DESC')->paginate(30);

            }else{
          return response()->json([
            'status' => 'error',
            'message' => 'Something went wrong, data could not be retrieved'
            ]);
        }
    }

      /**
     * This method retrieves
     *
     * blocked user(s)
     *
     */

     public function blockedUsers(){
         return User::where('status', 'blocked')->orderBy('created_at', 'DESC')->paginate(25);
     }

      /**
     * This method retrieves
     *
     * blocked user(s)
     *
     */

    public static function checkIfAdmin($user_id){
        $check = User::where(['status'=> 'active'])->where('user_level_id', 7)
                                                    ->first();
        if(!empty($check->id)){
            return true;
        }else{
            abort();
        }
    }

    /**
     * This method deletes
     *
     *  user
     *
     * @param $id
     */
    public static function delete($id){
        $delete = User::where('id', $id)->delete();
        if($delete){
            return response()->json([
                'status' =>'success',
                'message' => 'User deleted successfully'

                                     ]);
        }else{
            return response()->json([
                'status' =>'error',
                'message' => 'Something went wrong User not deleted successfully'
            ]);

        }
    }

    /**
     * This method updates
     *
     *  a user
     *
     * @param $id
     */
    public static function update(Request $request){
        $validation = Validator::make($request->all(),
        [
           "first_name" => "required|nullable",
           "last_name" => "required|nullable",
            "user_id" => "required"
        ]);

    if($validation->fails()){
        return response()->json([
            'status' => 403,
            'message' => $validation->errors(),
        ]);
    }
    $update = User::find($request['user_id']);

    if(!empty($request['name'])){
    $update->name = $request['name'];
    }

    $saved = $update->save();

    if($saved){
        return response()->json([
            'status' =>'success',
            'message' => 'User updated successfully',
            'updated' =>$update
                                 ]);
    }else{
        return response()->json([
            'status' =>'error',
            'message' => 'Something went wrong User not updated successfully'
        ]);

    }


    }

    /**
* This method updates the password
*
* for the users
*
*/
public static function changePassword(Request $request){
    $data = $request->all();

  $validation = Validator::make($request->all(),
    [ "user_id" => "required|exists:users,id",
      "old_password" =>"required|string",
      "new_password" => "required|min:6",
    ]);
  if($validation->fails()){
    return response()->json([
        'status' => 403,
        'message' => $validation->errors(),
    ]);
  }

    $confirm = User::where('id', $data['user_id'])->first();
    if(password_verify($data['old_password'], $confirm->password)){
      $update = User::find($data['user_id']);
      $update->password = bcrypt($data['new_password']);
      $update->save();
       return response()->json([
            'status' => 'success',
            'message' => 'Password updated successfully']);
    }else{
      return response()->json([
            'status' => 'error',
            'message' => 'Something went wrong, password not updated successfully. Please try again'
            ]);
    }
}

  /**
     * This method verifies a channel
     *
     * @param $id
     *
     * @return response
     */
    public static function verifyUser($id){
        if(!empty($id)){
            $user = User::where('id', $id)->first();
            $user->profile_verified = 'profile_verified';
            $ok = $user->save();
            $data = [
                'user_id' => $user->id,
                'action_id' => $user->id,
                'action' => 'User',
                'comment' => 'Congrats. Your account has been verified.',
                ];
              //  NewNotificationController::save($data);
            if($ok){
                return response()->json([
                    'status' => 'success',
                    'message' => 'User account verified successfully',
                ]);
            }else{
             return response()->json([
                 'status' => 'error',
                 'message' => 'Something went wrong user account not verified successfully. Please try again',
             ]);
            }
        }
      }

/**
* Uploading image
*
* @param $request
*/

public function uploadAvatar(Request $request){
    $data = $request->all();
    $validation = Validator::make($request->all(),
    [
    "avatar" => "required",
    "user_id" => "required"
    ]);

   if($validation->fails()){
    return response()->json([
        'status' => 403,
        'message' => $validation->errors(),
    ]);
   }

 if(!empty($request['avatar'])){
          $user = User::find($request['user_id']);
          $user->avatar = ImageController::uploadAvatar($request);
          $user->save();
          return response()->json([
            'status' => 'success',
            'message' => 'Profile image updated successfully'
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Profile image not updated successfully. Please try again.'
                ]);
        }
}

    /**
     * searches users
     *
     * @return true
     *
     * @param $search term
     *
     */
    public static function searchUser(Request $request){
        $validator = Validator::make($request->all(),
            [
                'term' => 'required',

            ]);

        if($validator->fails()){
            return redirect()->back()->withErrors($validator);
        }

        $result = User::where('email', $request['term'])->paginate(10);
        if(!empty($result)){
            $totaluser = self::totalUser();
            $allusers = self::get();
            $user = Auth::user();
            return view('/dashboard/src/html/hotel/user')->with([
                'admin'=>$user, 'total_users'=>$totaluser, 'all_users'=>$result,
            ]);
        }else{
            return view('/dashboard/src/html/hotel/error-page')->with([
                'msg'=> 'User email '.$request['term']. ' not found. Make sure the email is spelt correctly and try again']);
        }
    }

/**
* changes admin email
*
*@param $request
*/
public static function changeEmail($data){

   try{
       DB::transaction( function() use ($data){
    $code = mt_rand(1000, 9999);
    $confirm = User::where('id', $data['user_id'])->first();
      $update = User::find($data['user_id']);
      $update->email = $data['new_email'];
      $update->email_verified = Null;
      $update->save();
      EmailVerificationController::save($update->id, $code);
      // send verification mail
    //  $code = mt_rand(1000, 9999);
      /* return response()->json([
            'status' => 'success',
            'message' => 'Email updated successfully. Please note that you still have to verify this email']);*/

      });
    }catch(Exception $e){
     /* return response()->json([
            'status' => 'error',
            'message' => 'Something went wrong, email not updated successfully. Please try again'
            ]);*/
        }
  }




  /**
 * gets the total number of
 * user signed up today
 *
 * @return response
 *
 */
public static function totalUserToday(){
    $now = Carbon::today();
    return User::whereDate('created_at', $now)->count();
}

    /**
     * gets the total number of
     * user signed up today, week, month, year
     *
     *
     * @return response
     *
     */
    public static function getTotalUsers($duration =null){
        if (!empty($duration) && $duration == 1) {
            $duration = Carbon::today();
            $date_label = 'Today';
        } elseif ($duration == 2) {
            $now = Carbon::today();
            $duration = $now->subDays(2);
            $date_label = '48 hours ago';
        } elseif ($duration == 7) {
            $now = Carbon::today();
            $duration = $now->subDays(7);
            $date_label = '7 days ago';
        } elseif ($duration == 14) {
            $now = Carbon::today();
            $duration = $now->subDays(14);
            $date_label = '14 days ago';
        } elseif ($duration == 30) {
            $now = Carbon::today();
            $duration = $now->subDays(30);
            $date_label = '30 days ago';
        } elseif ($duration == 60) {
            $now = Carbon::today();
            $duration = $now->subDays(60);
            $date_label = '60 days ago';
        } elseif ($duration == 90) {
            $now = Carbon::today();
            $duration = $now->subDays(90);
            $date_label = '90 days ago';
        } elseif ($duration == 180) {
            $now = Carbon::today();
            $duration = $now->subDays(180);
            $date_label = '180 days ago';
        } elseif ($duration == 365) {
            $now = Carbon::today();
            $duration = $now->subDays(365);
            $date_label = '365 days ago';
        } elseif ($duration == null) {
            $date_label = 'All time';
            return User::whereNotNull('id')->count();

        }
        return User::whereDate('created_at', '>=', $duration)->count();
    }

/**
 * gets the total number of
 * user signed up this week
 *
 * @return response
 *
 */
public static function totalUserThisWeek(){
    $now = Carbon::now();
    $weekstart = $now->startOfWeek();
    return User::whereDate('created_at', '>=', $weekstart)->count();
}


/**
 * gets the total number of
 * user signed up this month
 *
 * @return response
 *
 */
public static function totalUserThisMonth(){
    $now = Carbon::now();
    $monthstart = $now->startOfMonth();
    return User::whereDate('created_at', '>=', $monthstart)->count();
}

/**
 * gets the total number of
 * user signed up this year
 *
 * @return response
 *
 */
public static function totalUserThisYear(){
    $now = Carbon::now();
    $yearstart = $now->startOfYear();
    return User::whereDate('created_at', '>=', $yearstart)->count();
}

/**
 * gets the total number of
 * all users signed on the platform
 *
 * @return response
 *
 */
public static function totalUser(){
    return User::whereNotNull('id')->count();
}

/**
 * Records last login
 *
 * @return response
 *
 */
public static function updateLastLogin($user_id){
    $user = User::find($user_id);
    $user->last_login_date = Carbon::now();
    $user->save();

}

/**
 * This method unsubscribes a
 *
 * user
 * @param email
 * @return a response
 */
public function unsubscribe($request){
    $validation = Validator::make($request->all(),
    [
    'email' => 'required|email',

    ]);

if($validation->fails()){
    return response()->json([
        'status' => 403,
        'message' => $validation->errors(),
    ]);
}
    $user = User::where('email', $request['email'])->first();
    if(!empty($user->email)){
        $user->receive_emails = 'unsubscribe';
        $user->save();
    }
}

/**
 * This method gets a
 *
 * userID
 *
 * @param $referral_code
 * @return a response
 */
public static function getUserId($referral_code){
    $user = User::where('referral_code', $referral_code)->first();
    if(!empty($user->id)){
        return $user->id;
    }else{
        return NULL;
    }
}


/**
 * This method gets a
 *
 * user details
 *
 *
 * @return a response
 */
public static function userDetails($user_id){
    $total_engagements = Post::where('user_id', $user_id)->sum('total_engagements');
    $user = User::where('id', $user_id)->withCount('post', 'userChannel')->first();
    return ['total_engagement' =>$total_engagements, 'users'=>$user];
}


/**
* Sets a password
*
*/
public static function setPassword($email, $password){
   $user = User::where('email', $email)->first();
   $user->password = Hash::make($password);
   $user->save();
    return response()->json([
            'status' => 'success',
            'message' => 'Password set successfully'
            ]);
   }


    /**
     * generates a unique referral code
     * for every user
     *
     */
    public static function generateReferralCode(){
        do {
            $code = time() - mt_rand(100000000, 999999999);
            $random = strtoupper(Str::random(2));
            $new_code = $random.'-'.$code;
            $exists = User::where('referral_code', $new_code)->exists();
        } while ($exists);

        return $new_code;
    }



    /**
     * get my referral code
     *
     * @param $user_id
     *
     */
    public static function myReferralCode($user_id){
        $myReferred = ReferralController::myReferred($user_id);
        $user = User::where('id', $user_id)->first();
        if(!empty($user->referral_code)){
            return [$user->referral_code, $myReferred];
        }else{
            $code = self::generateReferralCode();
            $user->referral_code = $code;
            $user->save();
        }
        return [$user->referral_code, $myReferred];
    }




}
