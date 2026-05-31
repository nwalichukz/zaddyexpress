<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Rider\RiderProfileController;
use App\Http\Controllers\Rider\RiderGuarantorController;
use App\Http\Controllers\UserProfile\UserProfileController;
use App\Http\Controllers\Api\EscrowTransactionController;

 
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

  // santum login
Route::get('/user/auth', [AuthController::class, 'auth']);
     
  



// login routes
Route::post('user/login', [AuthController::class, 'loginUser']);
Route::post('rider/login', [AuthController::class, 'loginRider']);

Route::get('user/logout', [AuthController::class, 'logout']);
Route::get('user/profile', [AuthController::class, 'profile']);


// user
Route::post('/user/create-email-password', [UserController::class, 'createEmailPassword']);
Route::post('/user/create', [UserController::class, 'create']); // createEmailPassword
Route::get('user/get-referral-code/{user_id}', [UserController::class, 'myReferralCode']);

Route::get('user/get/{id?}', [UserController::class, 'get']);
Route::post('user/update', [UserController::class, 'update']);
Route::get('user/delete/{id}', [UserController::class, 'delete']);
Route::post('user/change-password', [UserController::class, 'changePassword']);
Route::post('user/upload-img', [UserController::class, 'uploadAvatar']);

// Admin routes
Route::get('user/unblock/{id}', [UserController::class, 'unblock']);
Route::get('user/ban/{id}', [UserController::class, 'ban']);
Route::get('user/suspend/{id}', [UserController::class, 'suspend']);

Route::get('user/total-user-today', [UserController::class, 'totalUserToday']);
Route::get('user/get-my-referral-code/{id}', [UserController::class, 'myReferralCode']);
Route::get('user/total-user-this-week', [UserController::class, 'totalUserThisWeek']);
Route::get('user/total-user-this-month', [UserController::class, 'totalUserThisMonth']);
Route::get('user/total-user-this-year', [UserController::class, 'totalUserThisYear']);
Route::get('user/total-user', [UserController::class, 'totalUser']);
Route::get('user/user-details/{user_id}', [UserController::class, 'userDetails']);
Route::get('user/set-password/{email}/{password}', [UserController::class, 'setPassword']);

/*
|--------------------------------------------------------------------------
| Rider Profile API Routes
| Base prefix: /api  (set in RouteServiceProvider or bootstrap/app.php)
|--------------------------------------------------------------------------
|
| Auth layers:
|   auth:sanctum  → any authenticated user
|   auth:sanctum + role:admin → admin middleware (e.g. spatie/laravel-permission)
|
*/

// ── Public (no auth required) ─────────────────────────────────────────────────
// Dispatch / booking service queries for available riders
Route::get('riders/available', [RiderProfileController::class, 'availableInZone'])
     ->name('riders.available');

// ── Authenticated Rider ───────────────────────────────────────────────────────


    // Own profile
    Route::get('rider/me', [RiderProfileController::class, 'myProfile'])
         ->name('rider.me');

    // Create profile (one per user — enforced in controller)
    Route::post('riders', [RiderProfileController::class, 'store'])
         ->name('riders.store');

    // Update own profile fields + image
    Route::put('riders/{riderProfile}', [RiderProfileController::class, 'update'])
         ->name('riders.update');

    // GPS ping — called frequently by mobile app
    Route::patch('riders/{riderProfile}/location', [RiderProfileController::class, 'updateLocation'])
         ->name('riders.location');

    // Go online / offline
    Route::patch('riders/{riderProfile}/availability', [RiderProfileController::class, 'toggleAvailability'])
         ->name('riders.availability');



  // List guarantors for a specific rider
    // GET /api/rider-profiles/3/guarantors
    Route::get(
        'rider-profiles/{riderProfile}/guarantors',
        [RiderGuarantorController::class, 'byRider']
    )->name('rider.guarantors.index');

    // Add a guarantor to a rider
    // POST /api/rider-profiles/3/guarantors
    Route::post(
        'rider-profiles/{riderProfile}/guarantors',
        [RiderGuarantorController::class, 'store']
    )->name('rider.guarantors.store');

    // View a single guarantor
    // GET /api/guarantors/1
    Route::get(
        'guarantors/{guarantor}',
        [RiderGuarantorController::class, 'show']
    )->name('guarantors.show');

    // Update a guarantor
    // PUT /api/guarantors/1
    Route::put(
        'guarantors/{guarantor}',
        [RiderGuarantorController::class, 'update']
    )->name('guarantors.update');


// ── Admin Only ────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

    // List all riders (paginated, filterable, sortable)
    Route::get('riders', [RiderProfileController::class, 'index'])
         ->name('riders.index');

    // View any single rider
    Route::get('riders/{riderProfile}', [RiderProfileController::class, 'show'])
         ->name('riders.show');

    // Set status: active | inactive | suspended | banned
    Route::patch('riders/{riderProfile}/status', [RiderProfileController::class, 'changeStatus'])
         ->name('riders.status');

    // Post-trip trip counter (called by internal trip service)
    Route::patch('riders/{riderProfile}/trips/increment', [RiderProfileController::class, 'incrementTrips'])
         ->name('riders.trips.increment');

    // Post-trip rating update
    Route::patch('riders/{riderProfile}/review-rank', [RiderProfileController::class, 'updateReviewRank'])
         ->name('riders.reviewRank');

    // Hard delete + wipe image from storage
    Route::delete('riders/{riderProfile}', [RiderProfileController::class, 'destroy'])
         ->name('riders.destroy');

});

  // Logged-in user views their own profile
    // GET /api/user-profiles/me
    Route::get('user-profiles/me', [UserProfileController::class, 'myProfile'])
         ->name('user-profiles.me');

    // Create own profile (one per user — enforced in controller)
    // POST /api/user-profiles
    Route::post('user-profiles', [UserProfileController::class, 'store'])
         ->name('user-profiles.store');

    // Update own profile
    // PUT /api/user-profiles/{userProfile}
    Route::put('user-profiles/{userProfile}', [UserProfileController::class, 'update'])
         ->name('user-profiles.update');


         // ── Public ────────────────────────────────────────────────────────────────────

// Anyone can read a rider's reviews (customers browsing before booking)
// GET /api/rider-profiles/3/reviews
// GET /api/rider-profiles/3/reviews?min_score=4
Route::get(
    'rider-profiles/{riderProfile}/reviews',
    [ReviewController::class, 'byRider']
)->name('rider.reviews.index');

// Anyone can read a single review
// GET /api/reviews/7
Route::get(
    'reviews/{review}',
    [ReviewController::class, 'show']
)->name('reviews.show');


  // View all reviews the logged-in user has written
    // GET /api/reviews/mine
    Route::get('reviews/mine', [ReviewController::class, 'myReviews'])
         ->name('reviews.mine');

    // Submit a review for a rider (one per user per rider)
    // POST /api/rider-profiles/3/reviews
    Route::post(
        'rider-profiles/{riderProfile}/reviews',
        [ReviewController::class, 'store']
    )->name('rider.reviews.store');

    // Edit own review
    // PUT /api/reviews/7
    Route::put('reviews/{review}', [ReviewController::class, 'update'])
         ->name('reviews.update');

    // Delete own review
    // DELETE /api/reviews/7
    Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])
         ->name('reviews.destroy');


     // List all reviews across all riders (paginated + filterable)
    // GET /api/reviews
    // GET /api/reviews?rider_profile_id=3
    // GET /api/reviews?user_id=5
    // GET /api/reviews?min_score=1&max_score=3
    // GET /api/reviews?search=rude
    Route::get('reviews', [ReviewController::class, 'index'])
         ->name('reviews.index');


     // Rider views all their own applications
    // GET /api/job-applications/mine
    // GET /api/job-applications/mine?status=pending
    Route::get('job-applications/mine', [JobApplicationController::class, 'myApplications'])
         ->name('job-applications.mine');

    // Rider submits an application for a job
    // POST /api/jobs/5/applications
    Route::post(
        'jobs/{job}/applications',
        [JobApplicationController::class, 'store']
    )->name('job.applications.store');

    // Job poster views all applications on their job
    // GET /api/jobs/5/applications
    // GET /api/jobs/5/applications?status=pending
    Route::get(
        'jobs/{job}/applications',
        [JobApplicationController::class, 'byJob']
    )->name('job.applications.index');

    // View a single application (owner, job poster, or admin)
    // GET /api/job-applications/12
    Route::get(
        'job-applications/{jobApplication}',
        [JobApplicationController::class, 'show']
    )->name('job-applications.show');

    // Rider edits their own pending application
    // PUT /api/job-applications/12
    Route::put(
        'job-applications/{jobApplication}',
        [JobApplicationController::class, 'update']
    )->name('job-applications.update');

    // Job poster accepts or rejects an application
    // PATCH /api/job-applications/12/status
    // Body: { "status": "accepted" | "rejected" | "pending" }
    Route::patch(
        'job-applications/{jobApplication}/status',
        [JobApplicationController::class, 'changeStatus']
    )->name('job-applications.status');

    // Rider withdraws their own pending application
    // PATCH /api/job-applications/12/withdraw
    Route::patch(
        'job-applications/{jobApplication}/withdraw',
        [JobApplicationController::class, 'withdraw']
    )->name('job-applications.withdraw');

     // List all applications across all jobs (paginated + filterable)
    // GET /api/job-applications
    // GET /api/job-applications?status=pending
    // GET /api/job-applications?job_id=5
    // GET /api/job-applications?user_rider_id=3
    Route::get('job-applications', [JobApplicationController::class, 'index'])
         ->name('job-applications.index');

    // Hard-delete an application
    // DELETE /api/job-applications/12
    Route::delete(
        'job-applications/{jobApplication}',
        [JobApplicationController::class, 'destroy']
    )->name('job-applications.destroy');




// Browse all open jobs (riders looking for work)
// GET /api/jobs
// GET /api/jobs?status=open
// GET /api/jobs?mobility_type_needed=bike
// GET /api/jobs?price_type=negotiable
// GET /api/jobs?pickup_address=Lagos
// GET /api/jobs?search=grocery
// GET /api/jobs?active_only=true
// GET /api/jobs?min_price=500&max_price=5000
// GET /api/jobs?sort_by=price&sort_order=asc


    Route::get('jobs', [JobController::class, 'index'])
     ->name('jobs.index');
 
// View a single job with all its applications
// GET /api/jobs/5
Route::get('jobs/{job}', [JobController::class, 'show'])
     ->name('jobs.show');



    // View all jobs posted by the logged-in user
    // GET /api/jobs/mine
    // GET /api/jobs/mine?status=open
    Route::get('jobs/mine/list', [JobController::class, 'myJobs'])
         ->name('jobs.mine');
 
    // Post a new job
    // POST /api/jobs
    Route::post('jobs', [JobController::class, 'store'])
         ->name('jobs.store');
 
    // Edit an open job
    // PUT /api/jobs/5
    Route::put('jobs/{job}', [JobController::class, 'update'])
         ->name('jobs.update');
 
    // Move job through status lifecycle (open → matched → in_progress → completed)
    // PATCH /api/jobs/5/status
    // Body: { "status": "matched" }
    Route::patch('jobs/{job}/status', [JobController::class, 'changeStatus'])
         ->name('jobs.status');
 
    // Shorthand: mark a job as delivered/completed and stamp delivered_at
    // PATCH /api/jobs/5/deliver
    Route::patch('jobs/{job}/deliver', [JobController::class, 'markDelivered'])
         ->name('jobs.deliver');
 
    // Cancel an open or matched job
    // PATCH /api/jobs/5/cancel
    Route::patch('jobs/{job}/cancel', [JobController::class, 'cancel'])
         ->name('jobs.cancel');
 
    // Push the expiry date forward on an open job
    // PATCH /api/jobs/5/extend
    // Body: { "expires_at": "2025-12-31 23:59:59" }
    Route::patch('jobs/{job}/extend', [JobController::class, 'extendExpiry'])
         ->name('jobs.extend');


    // Hard-delete a job and all its applications
    // DELETE /api/jobs/5
    Route::delete('jobs/{job}', [JobController::class, 'destroy'])
         ->name('jobs.destroy');



    // -----------------------------------------------------------------
        // CRUD
        // -----------------------------------------------------------------
 
        // GET    /api/v1/escrow-transactions
        // Query params: status, user_id, rider_profile_id, release_trigger, per_page
        Route::get('/', [EscrowTransactionController::class, 'index'])
            ->name('escrow.index');
 
        // POST   /api/v1/escrow-transactions
        Route::post('/', [EscrowTransactionController::class, 'store'])
            ->name('escrow.store');
 
        // GET    /api/v1/escrow-transactions/{escrowTransaction}
        Route::get('/{escrowTransaction}', [EscrowTransactionController::class, 'show'])
            ->name('escrow.show');
 
        // PUT    /api/v1/escrow-transactions/{escrowTransaction}
        Route::put('/{escrowTransaction}', [EscrowTransactionController::class, 'update'])
            ->name('escrow.update');
 
        // DELETE /api/v1/escrow-transactions/{escrowTransaction}
        Route::delete('/{escrowTransaction}', [EscrowTransactionController::class, 'destroy'])
            ->name('escrow.destroy');
 
        // -----------------------------------------------------------------
        // STATUS TRANSITION ACTIONS
        // -----------------------------------------------------------------
 
        // POST /api/v1/escrow-transactions/{escrowTransaction}/hold
        // Called after payment gateway confirms funds received.
        // Moves: pending → held
        Route::post('/{escrowTransaction}/hold', [EscrowTransactionController::class, 'hold'])
            ->name('escrow.hold');
 
        // POST /api/v1/escrow-transactions/{escrowTransaction}/release
        // Body (OTP trigger only): { "otp": "123456" }
        // Moves: held → released
        Route::post('/{escrowTransaction}/release', [EscrowTransactionController::class, 'release'])
            ->name('escrow.release');
 
        // POST /api/v1/escrow-transactions/{escrowTransaction}/refund
        // Body (optional): { "reason": "..." }
        // Moves: held → refunded
        Route::post('/{escrowTransaction}/refund', [EscrowTransactionController::class, 'refund'])
            ->name('escrow.refund');
 
        // POST /api/v1/escrow-transactions/{escrowTransaction}/dispute
        // Body: { "reason": "..." }
        // Moves: held → disputed
        Route::post('/{escrowTransaction}/dispute', [EscrowTransactionController::class, 'dispute'])
            ->name('escrow.dispute');

// Bank Routes
Route::get('/bank-accounts',           [BankAccountController::class, 'index'])->name('bank-accounts.index');
Route::get('/bank-accounts/create',    [BankAccountController::class, 'create'])->name('bank-accounts.create');
Route::post('/bank-accounts',          [BankAccountController::class, 'store'])->name('bank-accounts.store');
Route::get('/bank-accounts/{bankAccount}',       [BankAccountController::class, 'show'])->name('bank-accounts.show');
Route::get('/bank-accounts/{bankAccount}/edit',  [BankAccountController::class, 'edit'])->name('bank-accounts.edit');
Route::put('/bank-accounts/{bankAccount}',       [BankAccountController::class, 'update'])->name('bank-accounts.update');
Route::delete('/bank-accounts/{bankAccount}',    [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');







