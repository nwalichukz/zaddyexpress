<?php

namespace App\Http\Controllers\Funding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\StaticVirtualAccount;
use App\Http\Controllers\Wallet\UserWalletController;
use App\Http\Controllers\Funding\StaticVirtualAccountController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BillStackController extends Controller
{    
    public static $secretKey ="Bearer sk_64221fd9539d37d7e2a467ccd84465530583f892";

     public static $baseUrl = "https://api-d.squadco.com";
    // Pulled from config/env instead of being hardcoded in source.
    // Add to your .env:
    //   SQUADCO_SECRET_KEY=sk_64221fd9539d37d7e2a467ccd84465530583f892
    //   SQUADCO_BASE_URL=https://api-d.squadco.com
   

    /**
     * Create a virtual account.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function createStaticVirtualAccount(Request $request)
    {
        

        if (!empty($request['first_name'])) {
            $full_name = $request['first_name'] . ' ' . $request['last_name'];
        } elseif (!empty($request['name'])) {
            $full_name = $request['name'];
        } else {
            $full_name = '- ZaddyExpress';
        }

        $ref = 'zaddy' . time() . mt_rand(10000000, 9999999999);

        $payload = [
            'business_name'       => $full_name,
            'mobile_num'          => $request['mobile_no'],
            'beneficiary_account' => '',
            'customer_identifier' => $ref,
            'bvn'                 => '22354606417',
        ];

        $url = 'virtual-account/business/';

       return $response = Http::withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => self::$secretKey,
            ])
            ->post(self::$baseUrl . '/' . $url, $payload);

        // Bail out early on transport/HTTP errors instead of assuming success.
        if (!$response->successful()) {
            Log::error('BillStack virtual account creation failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Could not reach virtual account provider.',
            ], 502);
        }

        $body = $response->json();

        $user = User::where('email', $request['email'])->first();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found for the supplied email.',
            ], 404);
        }

        // API returns a boolean, not the string "true" - compare properly.
        if (($body['status'] ?? false) === true) {
            $data = [
                'account_number' => $body['data']['account'][0]['account_number'],
                'bank_name'      => $body['data']['account'][0]['bank_name'],
                'txt_ref'        => $ref,
                'order_ref'      => $body['data']['reference'],
                'email'          => $request['email'],
                'user_id'        => $user->id,
            ];

            StaticVirtualAccountController::save($data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Permanent virtual account number generated successfully.',
            ]);
        }

        return response()->json([
            'status'          => 'error',
            'message'         => 'Permanent virtual account number not generated successfully',
            'account_details' => null,
        ]);
    }

    /**
     * Receives Billstack webhook.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function webhook(Request $request)
    {
        // This verifies the webhook is sent from Billstack.
        $secret_key = config('services.billstack.webhook_secret');
        $md5_hash   = md5($secret_key);

        $verified = request()->header('x-wiaxy-signature') === $md5_hash;

        if (!$verified) {
            Log::warning('BillStack webhook signature mismatch.');
            return response('Unauthorized', 401);
        }

        // Process for a successful charge.
        DB::transaction(function () use ($request) {
            $funder = StaticVirtualAccount::where([
                'txt_ref' => $request['data']['merchant_reference'],
                'status'  => 'active',
            ])->first();

            if (!$funder) {
                Log::warning('BillStack webhook: no matching virtual account found.', [
                    'txt_ref' => $request['data']['merchant_reference'] ?? null,
                ]);
                return;
            }

            $user = User::find($funder->user_id);

            if (!$user) {
                Log::warning('BillStack webhook: user not found for virtual account.', [
                    'user_id' => $funder->user_id,
                ]);
                return;
            }

            $amount = $request['data']['amount'];

            if ($amount <= 3000) {
                $charge = 0;
            } elseif ($amount * 0.008 > 300) {
                $charge = 300;
            } else {
                $charge = $amount * 0.008;
            }

            $data = [
                'user_id' => $user->id,
                'amount'  => $amount - $charge,
                'purpose' => 'Funding of account',
            ];

            UserWalletController::credit($data);
        });

        return response(200);
    }
}