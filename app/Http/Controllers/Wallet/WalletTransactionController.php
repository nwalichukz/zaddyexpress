<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WalletTransaction;
use Carbon\Carbon;

class WalletTransactionController extends Controller
{
    /**
     * saves a transaction
     *
     * @param
     *
     */
    public static function save(array $data){
        $save = new WalletTransaction;
         $save->user_id = $data['user_id'];
            $save->amount = $data['amount'];
            $save->transaction_type = $data['transaction_type'];
            $save->purpose = $data['purpose'];
            $save->save();
            return $save->id;

    }

    /***
     * returns transaction history
     *
     * @param $user_id
     *
     */
    public static function getUserHistory($user_id){
        return WalletTransaction::where('user_id', $user_id)->orderBy('created_at', 'DESC')->simplePaginate(10);
    }


    /***
     * returns transaction history
     * by ID
     * @param id
     *
     */
    public static function getHistory($id = null){
        if(!empty($id)){
            return $data = WalletTransaction::where('id', $id)->with(['user'])->orderBy('created_at', 'DESC')->first();
        }elseif(empty($id)){
            return $data = WalletTransaction::where('id', '!=', null)->with(['user'])
                                              ->orderBy('created_at', 'DESC')->paginate(100);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong, data could not be retrieved'
            ]);
        }
    }

    /***
     * returns transaction history
     * by ID
     * @param id
     *
     */
    public static function getFundingTransactions($user_id = null){
        if(!empty($user_id)){
            return $data = WalletTransaction::where(['user_id'=>$user_id, 'purpose'=>'Funding of account'])
                                                   ->with(['user'])->orderBy('created_at', 'DESC')->paginate(50);
        }elseif(empty($user_id)){
            return $data = WalletTransaction::where(['purpose'=>'Funding of account'])->with(['user'])
                ->orderBy('created_at', 'DESC')->paginate(50);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong, data could not be retrieved'
            ]);
        }
    }


    /***
     * returns user all transaction history
     * by ID
     * @param id
     *
     */
    public static function getUserTransactions($user_id = null){
        if(!empty($user_id)){
            return $data = WalletTransaction::where(['user_id'=>$user_id])
                ->where('purpose', '!=', 'Funding of account')
                ->with(['user'])->orderBy('created_at', 'DESC')->paginate(100);
        }elseif(empty($user_id)){
            return $data = WalletTransaction::where('purpose', '!=', 'Funding of account')
                ->with(['user'])
                ->orderBy('created_at', 'DESC')->paginate(100);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong, data could not be retrieved'
            ]);
        }
    }



    /***
     * returns transaction history
     * for today
     *
     *
     */
    public static function getCreditHistoryToday(){
        $today = Carbon::today();
        return WalletTransaction::whereDate('created_at', $today)
            ->where('transaction_type', 'credit')
            ->sum('amount');
    }

    /***
     * returns transaction history
     * for today
     *
     *
     */
    public static function getDebitHistoryToday(){
        $today = Carbon::today();
        return WalletTransaction::whereDate('created_at', $today)
            ->where('transaction_type', 'debit')
            ->sum('amount');
    }

    /***
     * returns transaction history
     * aggregates today, week, month, this year,
     *
     * $debir or credit
     */
    public static function getDebitAndCreditHistory($duration=null){
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
        }elseif ($duration == 'week') {
            $now = Carbon::today();
            $duration = $now->startOfWeek();
            $date_label = 'This week';
        }elseif ($duration == 'month') {
            $now = Carbon::today();
            $duration = $now->startOfMonth();
            $date_label = 'This Month';
        }elseif ($duration == 'year') {
            $now = Carbon::today();
            $duration = $now->startOfYear();
            $date_label = 'This Year';
        }elseif ($duration == null) {
            $date_label = 'All time';
            $transaction = WalletTransaction::whereNotNull('id')->get();
            return ['total_no'=>$transaction->count(),
                'sum_total'=>$transaction->sum('amount'),
                'date_label'=>$date_label,
            'date_label'=>$date_label??null];
        }

        $transaction = WalletTransaction::whereDate('created_at', '>=', $duration)->get();
        return ['total_no'=>$transaction->count(),
            'sum_total'=>$transaction->sum('amount'),
            'date_label'=>$date_label??null,
                    ];
    }

    /***
     * returns transaction history
     * for today
     *
     *
     */
    public static function getTotalHistoryToday(){
        $today = Carbon::today();
        return WalletTransaction::whereDate('created_at', $today)
                                                      ->sum('amount');
    }


    /***
     * returns transaction history
     * for this week
     */
    public static function getCreditHistoryThisWeek(){
        $now = Carbon::today();
        $weekstart = $now->startOfWeek();
        return WalletTransaction::whereDate('created_at', '>=', $weekstart)
            ->where('transaction_type', 'credit')
            ->sum('amount');
    }

    /***
     * returns transaction history
     * for this week
     */
    public static function getDebitHistoryThisWeek(){
        $now = Carbon::today();
        $weekstart = $now->startOfWeek();
        return WalletTransaction::whereDate('created_at', '>=', $weekstart)
            ->where('transaction_type', 'debit')
            ->sum('amount');
    }

    /***
     * returns transaction history
     * for this week
     */
    public static function getTotalHistoryThisWeek(){
        $now = Carbon::today();
        $weekstart = $now->startOfWeek();
        return WalletTransaction::whereDate('created_at', '>=', $weekstart)
                                                                  ->sum('amount');
    }

     /***
     * returns transaction history
     * for this week
     */
    public static function getTotalHistoryThisMonth(){
        $now = Carbon::today();
        $monthstart = $now->startOfMonth();
        return WalletTransaction::whereDate('created_at', '>=', $monthstart)
                                                                  ->sum('amount');
    }

    /***
     * returns transaction history
     * for this month
     */
    public static function getCreditHistoryThisMonth(){
        $now = Carbon::today();
        $monthstart = $now->startOfMonth();
        return WalletTransaction::whereDate('created_at', '>=', $monthstart)
            ->where('transaction_type', 'credit')
            ->sum('amount');


    }


    /***
     * returns transaction history
     * for this month
     */
    public static function getDebitHistoryThisMonth(){
        $now = Carbon::today();
        $monthstart = $now->startOfMonth();
        $transaction = WalletTransaction::whereDate('created_at', '>=', $monthstart)
            ->where('transaction_type', 'debit')->get();
        return ['total_no'=>$transaction->count(),
            'sum_total'=>$transaction->sum('amount')];


    }


    /***
     * returns transaction history
     * sum for this year
     */
    public static function getCreditHistoryThisYear (){
        $now = Carbon::today();
        $yearstart = $now->startOfYear();
        $transaction = WalletTransaction::whereDate('created_at', '>=', $yearstart)
            ->where('transaction_type', 'credit')->get();
        return ['total_no'=>$transaction->count(),
            'sum_total'=>$transaction->sum('amount')];
    }


    /***
     * returns transaction history
     * sum for this year
     */
    public static function getDebitHistoryThisYear (){
        $now = Carbon::today();
        $yearstart = $now->startOfYear();
        $transaction = WalletTransaction::whereDate('created_at', '>=', $yearstart)
            ->where('transaction_type', 'debit')->get();
        return ['total_no'=>$transaction->count(),
            'sum_total'=>$transaction->sum('amount')];
    }

    /***
     * returns transaction history
     * sum for this year
     */

    public static function getTotalHistoryThisYear (){
        $now = Carbon::today();
        $yearstart = $now->startOfYear();
        $transaction = WalletTransaction::whereDate('created_at', '>=', $yearstart)->get();
        return ['total_no'=>$transaction->count(),
            'sum_total'=>$transaction->sum('amount')];
    }


    /***
     * returns transaction history
     * sum for this year
     */
    public static function getGrandTotal(){

        $transaction = WalletTransaction::whereNotNull('id')->get();
        return ['total_no'=>$transaction->count(),
            'sum_total'=>$transaction->sum('amount')];

    }


     /***
     * returns transaction history
     * sum for this year
     */
    public static function userGrandTotal($user_id){

        return WalletTransaction::where('user_id', $user_id)->sum('amount');
      
    }
}
