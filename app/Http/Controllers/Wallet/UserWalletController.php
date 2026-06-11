<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\UserWallet;
use App\Models\WalletTransaction;
use DB;

class UserWalletController extends Controller
{
    public static $minimum_balance = 0;

    /**
     * MY WALLET — authenticated user's own wallet balance.
     * GET /api/wallet/me
     */
    public function myWallet(Request $request): JsonResponse
    {
        $wallet = UserWallet::where('user_id', $request->user()->id)->first();

        if (! $wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found for this account.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $wallet,
        ]);
    }

    /**
     * MY TRANSACTIONS — authenticated user's wallet transaction history.
     * GET /api/wallet/transactions
     */
    public function myTransactions(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 15), 100);

        $transactions = WalletTransaction::where('user_id', $request->user()->id)
                                          ->orderBy('created_at', 'desc')
                                          ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $transactions,
        ]);
    }
    /**
     * saves the data to  the db
     * this is the initialization process
     * of a user
     * @param $data
     */
    public static function save($user_id){
        //$user = User::where('id', $user_id)->first();
        DB::transaction(function() use ($user_id){
            $value = 0;
               $save = new UserWallet;
                $save->user_id = $user_id;
               $save->wallet_no = self::getWalletNo();
                $save->balance = $value;
                $save->save();

        });
    }

    /**
     * generates a unique wallet no
     * for every user
     *
     */
    public static function getWalletNo(){
        $walletno = mt_rand(1000000000, 9999999999);

        $query = UserWallet::where('wallet_no', '!=', $walletno)->first();
        while($query = true){

            return $walletno;

        }

    }

    /**
     * credit a user
     *
     * @param $request
     */

    public static function credit($request){
        $data = [
            'user_id' => $request['user_id'],
            'amount' => $request['amount'],
            'transaction_type' => 'credit',
            'purpose' => $request['purpose'],
            'comment' => 'Your wallet has been credited '.$request['amount'],
            'action_id' => $request['user_id'],
            'action' => 'Wallet',
        ];

        $result = DB::transaction(function() use ($request, $data){
            $credit = UserWallet::where('user_id', $request['user_id'])->with(['user'])->lockForUpdate()->first();
            $credit->balance = $credit->balance + $request['amount'];
            $credit->save();
            // NewNotificationController::save($data);
           return WalletTransactionController::save($data);
           // Mailer::creditMail($credit->user->email, $request['amount'], $credit->balance, $request['purpose']);
            //return $credit;

               });
          return $result;

    }

    /**
     *  credits a user
     * @param $request
     *
     */
    public static function makeCredit(Request $request){
       $credit = self::credit($request);
        if($credit){
           // return $credit;
            // Mailer::creditMail($credit->user->email, $request['amount'], $credit->balance);

            return response()->json([
                'status' => 'success',
                'message' => $request['amount'].' credited to your wallet successfully',
            ]);
        }else{
            return response()->json([
                'status' => 'success',
                'message' => 'Something went wrong, your wallet could not be credited. Please try again',
            ]);
        }
    }

    /**
     * debit a user
     *
     * @param $request
     */
    public static function debit($request){
        $data = [
            'user_id' => $request['user_id'],
            'amount' => $request['amount'],
            'transaction_type' => 'debit',
            'purpose' => $request['purpose'],
            'comment' => 'Your wallet has been debited '.$request['amount'].' for '.$request['purpose'],
            'action_id' => $request['user_id'],
            'action' => 'Wallet',
        ];

           $result = DB::transaction(function() use ($request, $data){
                $debit = UserWallet::where('user_id', $request['user_id'])->with(['user'])->lockForUpdate()->first();
                if($debit->balance >= $request['amount']) {
                    $debit->balance = $debit->balance - $request['amount'];
                    $debit->save();
                    // NewNotificationController::save($data);
                    return WalletTransactionController::save($data);
                    //  Mailer::debitMail($debit->user->email, $request['amount'], $debit->balance, $request['purpose']);
                }else{
                    return false;
                }

            });

           return $result;


    }

    /**
     *
     * debits a user
     * @param $user_id, $amount
     *
     */
    public static function makeDebit(Request $request){
        $check = self::checkAmt($request['user_id'], $request['amount']);
        if($check){
            $debit = self::debit($request);
            if($debit){
               //return $debit;
                return response()->json([
                    'status' => 'success',
                    'message' => $request['amount'].' debited from your wallet',
                ]);
            }else{

                return response()->json([
                    'status' => 'error',
                    'message' => 'Something went wrong your wallet could not be debited. Please try again',
                ]);

            }
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'You don not have sufficient balance in your wallet.'
            ]);

        }

    }

    /**
     *
     * checks if amount is
     * greater than balance
     * @param $user_id, $amount
     *
     */
    public static function checkAmt($user_id, $amount){

            //$new_amout = 300;
            $wallet = UserWallet::where('user_id', $user_id)->lockForUpdate()->first();
            if ($wallet->balance >= $amount) {
                return true;
            } else {
                return false;
            }

    }

    /**
     *
     * debits a user
     * @param $user_id, $amount
     *
     */
    public static function get($id = null){
        if(!empty($id)){
            return $data = UserWallet::where('id', $id)->with(['user'])->first();
        }elseif(empty($id)){
            return $data = UserWallet::where('user_id', '!=', null)->with(['user'])->paginate(20);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong, data could not be retrieved'
            ]);
        }
    }

    /**
     *
     * get balance
     * @param $user_id
     *
     */
    public static function balance($user_id){
        if(!empty($user_id)){
            $balance = UserWallet::where('user_id', $user_id)->lockForUpdate()->first();
            return $balance->balance;
        }elseif(empty($user_id)){

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong, balance could not be retrieved. Please try again'
            ]);
        }
    }

    /**
     *
     * returns a user details for transfer
     * confirmation
     * @param $sender_user_id, $receiver_wallet_no
     *
     */
    public static function confirmUserWalletNo($receiver_wallet_no){
        $receiver_wallet_details = UserWallet::where('wallet_no', $receiver_wallet_no)->with('user')->first();
        if(!empty($receiver_wallet_details->user->id)){
            return response()->json([
                'status' => 'success',
                'message' => 'Wallet ID is correct',
                'data' => $receiver_wallet_details,
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid wallet ID. Please correct ID and try again',
            ]);
        }
    }

    /**
     *
     * transfers money to another user in the paltform
     *
     * @param $sender_user_id, $receiver_wallet_no, $amount
     * @return $response
     */
    public static function transferToAnotherUserWalletId(Request $request){
        $validation = Validator::make($request->all(),
            [
                "sender_user_id" => "required",
                "receiver_wallet_no" =>"required",
                "amount"=>"required"
            ]);

        if($validation->fails()){
            return response()->json([
                'status' => 403,
                'message' => $validation->errors(),
            ]);
        }
        if(self::checkAmt($request['sender_user_id'], $request['amount'])){
            try{
                $credit = UserWallet::where('wallet_no', $request['receiver_wallet_no'])->with(['user'])->lockForUpdate()->first();
                $debit = UserWallet::where('user_id', $request['sender_user_id'])->with(['user'])->lockForUpdate()->first();
                DB::transaction(function() use ($request, $credit, $debit){

                    if($debit->balance >= $request['amount'] && is_int($request['amount'])){
                        $debit->balance = $debit->balance - $request['amount'];
                        $debit->save();
                        $debit_transaction_record = [
                            'user_id' => $request['sender_user_id'],
                            'amount' => $request['amount'],
                            'transaction_type' => 'debit',
                            'purpose' => 'transfer',
                            'receiver_id' => $credit->user_id,
                        ];
                        WalletTransactionController::save($debit_transaction_record);
                    }
                    // $credit = Wallet::where('wallet_no', $request['receiver_wallet_no'])->with(['user'])->first();
                    $credit->balance = $credit->balance + $request['amount'];
                    $credit->save();
                    $credit_transaction_record = [
                        'user_id' => $credit->user_id,
                        'amount' => $request['amount'],
                        'transaction_type' => 'credit',
                        'purpose' => 'transfer',
                    ];
                    WalletTransactionController::save($credit_transaction_record);
                });

                return response()->json([
                    'status' => 'success',
                    'message' => $request['amount'].' transfered successfully to '.$credit->user->first_name,
                ]);
            }catch(Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Something went wrong transfer could not be completed successfully.
                 Please try again',
                ]);

            }
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have sufficient balance in your wallet to carry out this transaction',
            ]);

        }



    }

    /**
     *
     * transfers money to another user in the paltform
     *
     * @param $sender_user_id, $receiver_wallet_no, $amount
     * @return $response
     */
    public static function transferFromUserToOrganizationWalletId(Request $request){
        if(self::checkAmt($request['sender_user_id'], $request['amount'])){
            try{
                $credit = OrganizationWallet::where('wallet_no', $request['receiver_wallet_no'])->with(['user'])->lockForUpdate()->first();
                $debit = UserWallet::where('user_id', $request['sender_user_id'])->with(['user'])->lockForUpdate()->first();
                DB::transaction(function() use ($request, $credit, $debit){

                    if($debit->balance >= $request['amount'] && is_int($request['amount'])){
                        $debit->balance = $debit->balance - $request['amount'];
                        $debit->save();
                        $debit_transaction_record = [
                            'user_id' => $request['sender_user_id'],
                            'amount' => $request['amount'],
                            'transaction_type' => 'debit',
                            'purpose' => 'transfer',
                            'receiver_id' => $credit->user_id,
                        ];
                        WalletTransactionController::save($debit_transaction_record);
                    }
                    // $credit = Wallet::where('wallet_no', $request['receiver_wallet_no'])->with(['user'])->first();
                    $credit->balance = $credit->balance + $request['amount'];
                    $credit->save();
                    $credit_transaction_record = [
                        'user_id' => $credit->user_id,
                        'amount' => $request['amount'],
                        'transaction_type' => 'credit',
                        'purpose' => 'transfer',
                    ];
                    OrganizationTransactionHistoryController::save($credit_transaction_record);
                });

                return response()->json([
                    'status' => 'success',
                    'message' => $request['amount'].' transfered successfully to '.$credit->user->name,
                ]);
            }catch(Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Something went wrong transfer could not be completed successfully.
                 Please try again',
                ]);

            }
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have sufficient balance in your wallet to carry out this transaction',
            ]);

        }



    }

    /**
     *
     * returns a total wallet balance
     *
     *
     *
     */
    public static function getTotalCurrentBalnce(){
        return UserWallet::where('id', '!=', NULL)->lockForUpdate()->sum('balance');
    }

    /**
     *
     * checks if you can
     *
     * cash out
     *
     */
    public static function checkIfCanCashOut($user_id){
        $balance = UserWallet::where('user_id', $user_id)->first();
        if($balance->balance >= self::$minimum_balance){
            return true;
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Sorry you can not withdraw until you have up to '.self::$minimum_balance.' in your wallet',
            ]);
        }

   }

}
