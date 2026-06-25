<?php

namespace App\Http\Controllers\Job;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppNotification;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class JobController extends Controller
{
   // =====================================================================
    // INDEX — List all jobs (public with filters)
    // GET /api/jobs
    // Filters: ?status=open&mobility_type_needed=bike&price_type=fixed
    //          &pickup_address=Lagos&search=delivery&expires_after=2025-01-01
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $query = Job::with('user:id,name,email,mobile_number');
 
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
 
        if ($request->filled('mobility_type_needed')) {
            $query->where('mobility_type_needed', $request->mobility_type_needed);
        }
 
        if ($request->filled('price_type')) {
            $query->where('price_type', $request->price_type);
        }
 
        if ($request->filled('pickup_address')) {
            $query->where('pickup_address', 'like', '%' . $request->pickup_address . '%');
        }
 
        if ($request->filled('dropoff_address')) {
            $query->where('dropoff_address', 'like', '%' . $request->dropoff_address . '%');
        }
 
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('pickup_address', 'like', "%{$search}%")
                  ->orWhere('dropoff_address', 'like', "%{$search}%");
            });
        }
 
        // Only return jobs that haven't expired
        if ($request->boolean('active_only', false)) {
            $query->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
        }
 
        if ($request->filled('expires_after')) {
            $query->where('expires_at', '>', $request->expires_after);
        }
 
        // Price range filter
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
 
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
 
        // Sorting
        $sortBy       = $request->get('sort_by', 'posted_at');
        $sortOrder    = $request->get('sort_order', 'desc');
        $allowedSorts = ['posted_at', 'created_at', 'price', 'expires_at', 'title'];
 
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }
 
        $perPage = min((int) $request->get('per_page', 15), 100);
        $jobs    = $query->paginate($perPage);
 
        return response()->json([
            'success' => true,
            'data'    => $jobs,
        ]);
    }
 


public static function store($request)
{      $save = new Job;
        $save->user_id = $request['user_id'];
        $save->save();
        return $save->id;

}


    public function show(Job $job): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $job->load([
                'user:id,name,email,mobile_number',
                'applications.userRider:id,name,email,mobile_number',
            ]),
        ]);

    }
 
   
    /**
     * Get all jobs for a specific user ID.
     */
    public function myJobs($userId)
    {
        // Query the Job model directly using the user_id column
        $jobs = Job::where('user_id', $userId)
                   ->orderBy('created_at', 'desc')
                   ->with('user')->get();

        return response()->json([
            'success' => true,
            'data' => $jobs
        ]);
    }
    // =========================================================================
    // UPDATE — Edit a job (only while open)
    // PUT /api/jobs/{job}
    // =========================================================================
    public function update(Request $request, Job $job): JsonResponse
    {
        if (! $this->canManageJob($request, $job)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorised to edit this job.',
            ], 403);
        }
 
        // Can only edit open jobs
        if ($job->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'Only open jobs can be edited.',
            ], 422);
        }
 
        $validator = Validator::make($request->all(), [
            'title'                => ['sometimes', 'string', 'max:255'],
            'description'          => ['nullable', 'string', 'max:5000'],
            'pickup_address'       => ['sometimes', 'string', 'max:500'],
            'pickup_lat'           => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_lng'           => ['nullable', 'numeric', 'between:-180,180'],
            'dropoff_address'      => ['sometimes', 'string', 'max:500'],
            'mobility_type_needed' => ['nullable', 'string',
                                       Rule::in(['bike', 'van'])],
            'price'                => ['nullable', 'numeric', 'min:0'],
            'price_type'           => ['sometimes', 'string',
                                       Rule::in(['fixed', 'negotiable'])],
            'expires_at'           => ['nullable', 'date', 'after:now'],
        ]);
 
        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }
 
        $job->update($validator->validated());
 
        return response()->json([
            'success' => true,
            'message' => 'Job updated successfully.',
            'data'    => $job->fresh('user:id,name,email,mobile_number'),
        ]);
    }
 
    // =========================================================================
    // CHANGE STATUS — Move job through its lifecycle
    // PATCH /api/jobs/{job}/status
    // =========================================================================
    public function changeStatus(Request $request, Job $job): JsonResponse
    {
        if (! $this->canManageJob($request, $job)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorised to change the status of this job.',
            ], 403);
        }
 
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'string',
                         Rule::in(['open', 'matched', 'in_progress', 'completed', 'cancelled'])],
        ]);
 
        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }
 
        // Enforce valid status transitions
        $transitions = [
            'open'        => ['matched', 'cancelled'],
            'matched'     => ['in_progress', 'open', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            'completed'   => [],   // terminal — no further transitions
            'cancelled'   => [],   // terminal — no further transitions
        ];
 
        $currentStatus    = $job->status;
        $allowedNext      = $transitions[$currentStatus] ?? [];
 
        if (! in_array($request->status, $allowedNext)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot transition job from [{$currentStatus}] to [{$request->status}].",
                'allowed' => $allowedNext,
            ], 422);
        }
 
        $updateData = ['status' => $request->status];
 
        // Stamp delivered_at when job is marked completed
        if ($request->status === 'completed') {
            $updateData['delivered_at'] = now();
        }
 
        $job->update($updateData);

        AppNotification::create([
            'user_id' => $job->user_id,
            'type' => 'job_status_changed',
            'payload' => [
                'title' => 'Job Status Updated',
                'body' => "Your job \"{$job->title}\" is now {$request->status}.",
            ],
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Job status changed from [{$currentStatus}] to [{$request->status}].",
            'data'    => $job->fresh(),
        ]);
    }
 
    // =========================================================================
    // MARK DELIVERED — Shorthand to complete a job and stamp delivered_at
    // PATCH /api/jobs/{job}/deliver
    // =========================================================================
    public function markDelivered(Request $request, Job $job): JsonResponse
    {
        if (! $this->canManageJob($request, $job)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorised to mark this job as delivered.',
            ], 403);
        }
 
        if ($job->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Only in-progress jobs can be marked as delivered.',
            ], 422);
        }
 
        $job->update([
            'status'       => 'completed',
            'delivered_at' => now(),
        ]);

        $notifyUserIds = [$job->user_id];
        if ($job->acceptedApplication) {
            $notifyUserIds[] = $job->acceptedApplication->user_rider_id;
        }

        foreach (array_unique($notifyUserIds) as $notifyUserId) {
            AppNotification::create([
                'user_id' => $notifyUserId,
                'type' => 'job_delivered',
                'payload' => [
                    'title' => 'Job Delivered',
                    'body' => "Job \"{$job->title}\" has been marked as delivered.",
                ],
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Job marked as delivered.',
            'delivered_at' => $job->delivered_at,
            'data'         => $job->fresh(),
        ]);
    }
 
    // =========================================================================
    // CANCEL — Owner cancels an open or matched job
    // PATCH /api/jobs/{job}/cancel
    // =========================================================================
    public function cancel(Job $job)
    {
        if (! $this->canManageJob($request, $job)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorised to cancel this job.',
            ], 403);
        } 
 
        if (in_array($job->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => "A [{$job->status}] job cannot be cancelled.",
            ], 422);
        }
 
        $job->update(['status' => 'cancelled']);

        $notifyUserIds = [$job->user_id];
        if ($job->acceptedApplication) {
            $notifyUserIds[] = $job->acceptedApplication->user_rider_id;
        }

        foreach (array_unique($notifyUserIds) as $notifyUserId) {
            AppNotification::create([
                'user_id' => $notifyUserId,
                'type' => 'job_cancelled',
                'payload' => [
                    'title' => 'Job Cancelled',
                    'body' => "Job \"{$job->title}\" was cancelled.",
                ],
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Job cancelled successfully.',
            'data'    => $job->fresh(),
        ]);
    }

 
    // =========================================================================
    // EXTEND EXPIRY — Push the expiry date forward
    // PATCH /api/jobs/{job}/extend
    // =========================================================================
    public function extendExpiry(Request $request, Job $job): JsonResponse
    {
        if (! $this->canManageJob($request, $job)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorised to extend this job.',
            ], 403);
        }
 
        if ($job->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'Only open jobs can have their expiry extended.',
            ], 422);
        }
 
        $validator = Validator::make($request->all(), [
            'expires_at' => ['required', 'date', 'after:now'],
        ]);
 
        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }
 
        $job->update(['expires_at' => $request->expires_at]);
 
        return response()->json([
            'success'    => true,
            'message'    => 'Job expiry date extended.',
            'expires_at' => $job->expires_at,
        ]);
    }
 
    // =========================================================================
    // DESTROY — Hard-delete a job (admin only)
    // DELETE /api/jobs/{job}
    // =========================================================================
    public function destroy(Job $job): JsonResponse
    {
        $job->delete();
 
        return response()->json([
            'success' => true,
            'message' => 'Job deleted successfully.',
        ]);
    }
 
    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================
    private function validationError($errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors'  => $errors,
        ], 422);
    }

    private function canManageJob(Request $request, Job $job): bool
    {
        $user = $request->user();

        return $user && ($user->hasRole('admin') || (int) $job->user_id === (int) $user->id);
    }

}
