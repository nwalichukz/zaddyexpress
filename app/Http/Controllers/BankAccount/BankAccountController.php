<?php

namespace App\Http\Controllers\BankAccount;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BankAccount;

class BankAccountController extends Controller
{

 /**
     * Display all bank accounts
     */
    public function index()
    {
        $bankAccounts = BankAccount::latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Bank accounts retrieved successfully.',
            'data'    => $bankAccounts,
        ], 200);
    }



    /**
     * Store a new bank account
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_profile_id' => 'required|integer',
            'account_number'  => 'required|string|unique:bank_accounts,account_number',
            'bank_name'       => 'required|string',
            'status'          => 'nullable|string',
        ]);

        $bankAccount = BankAccount::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bank account created successfully.',
            'data'    => $bankAccount,
        ], 201);
    }



    /**
     * Show a single bank account
     */
    public function show(BankAccount $bankAccount)
    {
        return response()->json([
            'success' => true,
            'message' => 'Bank account retrieved successfully.',
            'data'    => $bankAccount,
        ], 200);
    }



    /**
     * Update a bank account
     */
    public function update(Request $request, BankAccount $bankAccount)
    {
        $validated = $request->validate([
            'user_profile_id' => 'required|integer',
            'account_number'  => 'required|string|unique:bank_accounts,account_number,' . $bankAccount->id,
            'bank_name'       => 'required|string',
            'status'          => 'nullable|string',
        ]);

        $bankAccount->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bank account updated successfully.',
            'data'    => $bankAccount,
        ], 200);
    }


    /**
     * Delete a bank account
     */
    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bank account deleted successfully.',
            'data'    => null,
        ], 200);
    }


}
