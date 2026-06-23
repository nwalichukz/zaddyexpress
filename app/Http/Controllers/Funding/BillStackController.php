<?php

namespace App\Http\Controllers\Funding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\StaticVirtualAccount;
use App\Http\Controllers\Wallet\UserWalletController;
use App\Http\Controllers\Funding\StaticVirtualAccountController;
use Validator, DB, Http;

class BillStackController extends Controller
{
    private static function authorizationHeader(): string
    {
        $secret = (string) config('services.billstack.secret_key');

        return str_starts_with($secret, 'Bearer ') ? $secret : 'Bearer '.$secret;
    }

    private static function baseUrl(): string
    {
        return (string) config('services.billstack.base_url');
    }



    /**
     * create a virtual account
     *
     * @param $data
     *
     * @return object
     */
    public static function createStaticVirtualAccount(Request $request){
        $validation = Validator::make($request->all(),
            [
                'email' => 'required|exists:users,email',//|exists:users|email',
                'first_name'=>'required',
                'last_name'=>'required',
                'mobile_no'=>'required|exists:users,mobile_no|min:11|max:12',

            ]);

        if($validation->fails()){
            return $validation->errors();
        }

        if(!empty($request['first_name'])) {
            $full_name = $request['first_name'] . ' ' . $request['last_name'];
        }elseif(!empty($request['name'])){
            $full_name = $request['name'];
        }else{
            $full_name     = "- Kuritr";
        }
        $ref = 'BSTA'.time().mt_rand(10000000, 9999999999);
        $payload = [
            "email" => $request['email'],
            "firstName"=>$request['first_name'],
            "lastName"=>$request['last_name'],
            "phone" => $request['mobile_no'],
            "reference"=>$ref,
            "bank"=>"PALMPAY",
        ];

         $response = Http::withHeaders(['Content-Type' => 'application/json',
                            'Authorization' => self::authorizationHeader()])
                            ->post(
                                self::baseUrl(),
                                $payload
                            );
        // return $payload;
        $user = User::where('email',$request['email'])->first();
        if ($response['status'] == 'true') {
            $data = [
                "account_number"=>$response['data']['account'][0]['account_number'],
                "bank_name"=>$response['data']['account'][0]['bank_name'],
                "txt_ref"=>$ref,
                "order_ref"=>$response['data']['reference'],
                "email"=>$request['email'],
                "user_id"=>$user->id,
            ];

            $bank = StaticVirtualAccountController::save($data);
            return response()->json([
                "status"=>"success",
                //"data"=>$response['data'],
                "message"=>"Permanent virtual account number generated successfully."
            ]);
        }else{
            return response()->json([
                "status"=>"error",
                "message"=>"Permanent virtual account number not generated successfully",
                "account_details"=>null
            ]);
        }
    }


    /**
     * create a virtual account
     * for event triggers
     * @param $data
     *
     * @return object
     */
    public static function createStaticVirtualAccountEvent($request){
       
        if(!empty($request['first_name'])) {
            $full_name = $request['first_name'] . ' ' . $request['last_name'];
        }elseif(!empty($request['name'])){
            $full_name = $request['name'];
        }else{
            $full_name     = "- Kuritr";
        }
        $ref = 'BSTA'.time().mt_rand(10000000, 9999999999);
        $payload = [
            "email" => $request['email'],
            "firstName"=>$request['first_name'],
            "lastName"=>$request['last_name'],
            "phone" => $request['mobile_no'],
            "reference"=>$ref,
            "bank"=>"PALMPAY",
        ];

         $response = Http::withHeaders(['Content-Type' => 'application/json',
                            'Authorization' => self::authorizationHeader()])
                            ->post(
                                self::baseUrl(),
                                $payload
                            );
        // return $payload;
        $user = User::where('email',$request['email'])->first();
        if ($response['status'] == 'true') {
            $data = [
                "account_number"=>$response['data']['account'][0]['account_number'],
                "bank_name"=>$response['data']['account'][0]['bank_name'],
                "txt_ref"=>$ref,
                "order_ref"=>$response['data']['reference'],
                "email"=>$request['email'],
                "user_id"=>$user->id,
            ];

            StaticVirtualAccountController::save($data);
        }else{
            /*return response()->json([
                "status"=>"error",
                "message"=>"Permanent virtual account number not generated successfully"
            ]);*/
        }
    }



    /**
     * Receives Billstack webhook
     * @return void
     */
    public function webhook(Request $request)
    {
         //This verifies the webhook is sent from Billstack
              $secret_key = (string) config('services.billstack.secret_key');
             $md5_hash = md5($secret_key);
             if(request()->header('x-wiaxy-signature') == $md5_hash){
                 $verified = true;

             }else{
                 $verified = false;
             } 


            // $verified = true;

        // if it is a charge event, verify and confirm it is a successful transaction
        if($verified === true) {

                    // process for successful charge
                    DB::transaction(function () use ($request) {
                        $funder = StaticVirtualAccount::where(['txt_ref' => $request['data']['merchant_reference'], 'status' => 'active'])->first();
                        
                        if ($funder->id) {
                            $user = User::where('id', $funder->user_id)->first();
                            if($request['data']['amount']*0.008 > 300){
                                $charge = 300;
                            }elseif($request['data']['amount']<=3000){
                                $charge = 0;
                            }else{
                               $charge = $request['data']['amount']*0.008; 
                            }
                            $data = [
                                'user_id' => $user->id,
                                'amount' => $request['data']['amount'] - $charge,
                                'purpose' => 'Funding of account',
                            ];
                            UserWalletController::credit($data);
                        }
                    });
                      
                    return response(200);

               

        }

    

      

    }


}
