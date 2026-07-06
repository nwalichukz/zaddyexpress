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
    public static $secretKey ="Bearer sk_64221fd9539d37d7e2a467ccd84465530583f892";

     public static $baseUrl = "https://api-d.squadco.com";





    /**
     * create a virtual account
     *
     * @param $data
     *
     * @return object
     */
    public static function createStaticVirtualAccount(Request $request){
                  return 12345;
    
       /* if(!empty($request['first_name'])) {
            $full_name = $request['first_name'] . ' ' . $request['last_name'];
        }elseif(!empty($request['name'])){
            $full_name = $request['name'];
        }else{
            $full_name     = "- ZaddyExpress";
        }
        $ref = 'zaddy'.time().mt_rand(10000000, 9999999999);
        $payload = [
            "business_name"=>$full_name,
            "mobile_num" => $request['mobile_no'],
            "beneficiary_account"=>"9017280136",
            "customer_identifier"=>$ref,
            "bvn"=>"22354606417",
        ];
        $url = "virtual-account/business/";
        return $response = Http::withHeaders(['Content-Type' => 'application/json',
                            'Authorization' => self::$secretKey])
                            ->post(
                                self::$baseUrl.'/'.$url,
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
        }*/
    }


    


    /**
     * Receives Billstack webhook
     * @return void
     */
    public function webhook(Request $request)
    {
         //This verifies the webhook is sent from Billstack
              $secret_key = "Bill_Stack-SEC-KEY-1f3c6fd0ea70df03e0e94e2e3cf42483";
             $md5_hash = md5($secret_key);
             if(request()->header('x-wiaxy-signature') == $md5_hash){
                 $verified = true;

             }else{
                 $verified = false;
             } 


            // $verified = true;

        // if it is a charge event, verify and confirm it is a successful transaction
        if($verified = true) {

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
