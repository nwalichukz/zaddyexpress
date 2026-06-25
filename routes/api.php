<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BankAccount\BankAccountController;
use App\Http\Controllers\Escrow\EscrowTransactionController;
use App\Http\Controllers\Job\JobApplicationController;
use App\Http\Controllers\Job\JobController;
use App\Http\Controllers\Job\JobItemController;
use App\Http\Controllers\Notification\AppNotificationController;
use App\Http\Controllers\Review\ReviewController;
use App\Http\Controllers\Rider\RiderGuarantorController;
use App\Http\Controllers\Rider\RiderProfileController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\UserProfile\UserProfileController;
use App\Http\Controllers\Wallet\UserWalletController;

/*
|--------------------------------------------------------------------------
| Public mobile routes
|--------------------------------------------------------------------------
|
| JWT is the v1 mobile auth strategy. /user/auth remains a Sanctum-only
| compatibility route and is not part of the mobile contract.
|
*/

Route::post('user/login', [AuthController::class, 'loginUser']);
Route::post('rider/login', [AuthController::class, 'loginRider']);
Route::post('user/create', [UserController::class, 'create']);
Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);

Route::prefix('auth')->group(function () {
    Route::post('user/login', [AuthController::class, 'loginUser']);
    Route::post('rider/login', [AuthController::class, 'loginRider']);
    Route::post('register', [UserController::class, 'create']);
});

Route::get('user/auth', [AuthController::class, 'auth']);

Route::get('riders/available', [RiderProfileController::class, 'availableInZone']);
Route::get('rider-profiles/{riderProfile}/reviews', [ReviewController::class, 'byRider']);
Route::get('reviews/{review}', [ReviewController::class, 'show']);
Route::get('reviews', [ReviewController::class, 'index']);
Route::get('jobs', [JobController::class, 'index']);
Route::get('jobs/{job}', [JobController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Authenticated JWT mobile routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->group(function () {
    Route::get('user', [AuthController::class, 'me']);
    Route::get('me', [AuthController::class, 'me']);
    Route::get('user/profile', [AuthController::class, 'profile']);
    Route::post('user/logout', [AuthController::class, 'logout']);
    Route::get('user/logout', [AuthController::class, 'logout']);

    Route::get('user/get-referral-code/{user_id}', [UserController::class, 'myReferralCode']);
    Route::get('user/get-my-referral-code/{id}', [UserController::class, 'myReferralCode']);
    Route::get('user/get/{id?}', [UserController::class, 'get']);
    Route::post('user/update', [UserController::class, 'update']);
    Route::post('user/change-password', [UserController::class, 'changePassword']);
    Route::post('user/upload-img', [UserController::class, 'uploadAvatar']);

    Route::get('rider/my-profile/{id?}', [RiderProfileController::class, 'myProfile']);
    Route::get('riders/by-user/{userId}', [RiderProfileController::class, 'byUser']);
    Route::post('riders/create', [RiderProfileController::class, 'store']);
    Route::put('riders/update/{riderProfile}', [RiderProfileController::class, 'update']);
    Route::patch('riders/{riderProfile}/location', [RiderProfileController::class, 'updateLocation']);
    Route::patch('riders/{riderProfile}/availability', [RiderProfileController::class, 'toggleAvailability']);

    Route::get('rider-profiles/{riderProfile}/guarantors', [RiderGuarantorController::class, 'byRider']);
    Route::post('rider-profiles/{riderProfile}/guarantors', [RiderGuarantorController::class, 'store']);
    Route::get('guarantors/{guarantor}', [RiderGuarantorController::class, 'show']);
    Route::put('guarantor/{guarantor}/update', [RiderGuarantorController::class, 'update']);

    Route::get('user-profiles/me', [UserProfileController::class, 'myProfile']);
    Route::post('user-profiles', [UserProfileController::class, 'store']);
    Route::put('user-profiles/{userProfile}', [UserProfileController::class, 'update']);

    Route::get('reviews/mine', [ReviewController::class, 'myReviews']);
    Route::post('rider-profiles/{riderProfile}/reviews', [ReviewController::class, 'store']);
    Route::put('reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('reviews/{review}', [ReviewController::class, 'destroy']);

    Route::get('job-applications/mine', [JobApplicationController::class, 'myApplications']);
    Route::post('jobs/{job}/applications', [JobApplicationController::class, 'store']);
    Route::get('jobs/{job}/applications', [JobApplicationController::class, 'byJob']);
    Route::get('job-applications/{jobApplication}', [JobApplicationController::class, 'show']);
    Route::put('job-applications/{jobApplication}', [JobApplicationController::class, 'update']);
    Route::patch('job-applications/{jobApplication}/status', [JobApplicationController::class, 'changeStatus']);
    Route::patch('job-applications/{jobApplication}/withdraw', [JobApplicationController::class, 'withdraw']);

    Route::get('my-jobs/{user_id}', [JobController::class, 'myJobs']);

 
    Route::post('jobs/create', [JobItemController::class, 'create']);
    Route::post('jobs', [JobController::class, 'store']);
    Route::put('jobs/{job}', [JobController::class, 'update']);
    Route::patch('jobs/{job}/status', [JobController::class, 'changeStatus']);
    Route::patch('jobs/{job}/deliver', [JobController::class, 'markDelivered']);
    Route::patch('jobs/{job}/cancel', [JobController::class, 'cancel']);
    Route::patch('jobs/{job}/extend', [JobController::class, 'extendExpiry']);

    Route::get('wallet/me', [UserWalletController::class, 'myWallet']);
    Route::get('wallet/transactions', [UserWalletController::class, 'myTransactions']);

    Route::get('notifications', [AppNotificationController::class, 'index']);
    Route::get('notifications/unread-count', [AppNotificationController::class, 'unreadCount']);
    Route::patch('notifications/{notification}/read', [AppNotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [AppNotificationController::class, 'markAllAsRead']);

    Route::apiResource('bank-accounts', BankAccountController::class)
        ->except(['create', 'edit'])
        ->parameters(['bank-accounts' => 'bankAccount']);

    Route::prefix('escrow-transactions')->group(function () {
        Route::get('/', [EscrowTransactionController::class, 'index']);
        Route::post('/', [EscrowTransactionController::class, 'store']);
        Route::get('{escrowTransaction}', [EscrowTransactionController::class, 'show']);
        Route::put('{escrowTransaction}', [EscrowTransactionController::class, 'update']);
        Route::delete('{escrowTransaction}', [EscrowTransactionController::class, 'destroy']);
        Route::post('{escrowTransaction}/hold', [EscrowTransactionController::class, 'hold']);
        Route::post('{escrowTransaction}/release', [EscrowTransactionController::class, 'release']);
        Route::post('{escrowTransaction}/refund', [EscrowTransactionController::class, 'refund']);
        Route::post('{escrowTransaction}/dispute', [EscrowTransactionController::class, 'dispute']);
    });
});

/*
|--------------------------------------------------------------------------
| Authenticated admin/internal routes
|--------------------------------------------------------------------------
|
| These are no longer public. Add a dedicated admin middleware when the API's
| role system is finalized.
|
*/

Route::middleware('auth:api')->prefix('admin')->group(function () {
    Route::get('users/total-today', [UserController::class, 'totalUserToday']);
    Route::get('users/total-this-week', [UserController::class, 'totalUserThisWeek']);
    Route::get('users/total-this-month', [UserController::class, 'totalUserThisMonth']);
    Route::get('users/total-this-year', [UserController::class, 'totalUserThisYear']);
    Route::get('users/total', [UserController::class, 'totalUser']);
    Route::get('users/{user_id}', [UserController::class, 'userDetails']);
    Route::get('users/{email}/set-password/{password}', [UserController::class, 'setPassword']);
    Route::delete('users/{id}', [UserController::class, 'delete']);

    Route::get('riders', [RiderProfileController::class, 'index']);
    Route::get('all-riders', [RiderProfileController::class, 'index']);
    Route::patch('riders/{riderProfile}/status', [RiderProfileController::class, 'changeStatus']);
    Route::patch('riders/{riderProfile}/trips/increment', [RiderProfileController::class, 'incrementTrips']);
    Route::patch('riders/{riderProfile}/review-rank', [RiderProfileController::class, 'updateReviewRank']);
    Route::delete('riders/{riderProfile}', [RiderProfileController::class, 'destroy']);

    Route::get('job-applications', [JobApplicationController::class, 'index']);
    Route::delete('job-applications/{jobApplication}', [JobApplicationController::class, 'destroy']);
    Route::delete('jobs/{job}', [JobController::class, 'destroy']);
});
 