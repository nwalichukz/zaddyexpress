<?php

namespace App\Http\Controllers\Job;

use App\Http\Controllers\Controller;
use App\Models\JobItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Job\JobController;
use App\Models\Job; // Add this
use Illuminate\Support\Facades\DB; // Add this
use Carbon\Carbon;

class JobItemController extends Controller
      {
    // =========================================================================
    // INDEX — Get all job items
    // GET /job-items
    // =========================================================================
    public function index(): JsonResponse
    {
        $jobItems = JobItem::latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Job items retrieved successfully.',
            'data'    => $jobItems,
        ], 200);
    }


    // =========================================================================
    // STORE — Create a new job item
    // POST /job-items
    // =========================================================================

    public function create(Request $request)
      {
         return "Working Now";
      }

   public function createR(Request $request)
      {          //return 4568;
    // 1. Validate
    $validator = Validator::make($request->all(), [
       'items'                            => ['required', 'array', 'min:1'],
    
    // Existence and Basic Info
    //'items.*.job_id'                   => ['required', 'integer', 'exists:jobs,id'],
    'items.*.title'                    => ['required', 'string', 'max:255'],
    'items.*.receiver_name'            => ['nullable', 'string', 'max:255'],
    'items.*.description'              => ['nullable', 'string', 'max:5000'],
    
    // Location Data
    'items.*.pickup_address'           => ['required', 'string', 'max:500'],
    'items.*.pickup_lat'               => ['nullable', 'numeric', 'between:-90,90'],
    'items.*.pickup_lng'               => ['nullable', 'numeric', 'between:-180,180'],
    'items.*.dropoff_address'          => ['required', 'string', 'max:500'],
    'items.*.dropoff_lat'              => ['nullable', 'numeric', 'between:-90,90'],
    'items.*.dropoff_lng'              => ['nullable', 'numeric', 'between:-180,180'],
    
    // Service Details
    'items.*.mobility_type_needed'     => ['nullable', 'string', Rule::in(['bike', 'van', 'truck', 'car'])],
    'items.*.price'                    => ['nullable', 'numeric', 'min:0'],
    'items.*.platform_fee'             => ['nullable', 'numeric', 'min:0'],
    'items.*.order_fee'                => ['nullable', 'numeric', 'min:0'],
    'items.*.total_fair_fee'           => ['nullable', 'numeric', 'min:0'],
    'items.*.price_type'               => ['sometimes', 'string', Rule::in(['fixed', 'negotiable'])],
    
    // Status & Timestamps
    'items.*.status'                   => ['nullable', 'string', Rule::in(['open', 'matched', 'in_progress', 'completed', 'cancelled'])],
    'items.*.expires_at'               => ['nullable', 'date', 'after:now'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 403,
            'message' => $validator->errors(),
        ]);
    }

    $validated = $validator->validated();
    $createdItems = [];

    // 2. Use a transaction to ensure all items are saved or none are
    DB::transaction(function() use ($request, $validated, &$createdItems) {
        
        // Assuming JobController::store returns the ID or the created Model
        // Ensure JobController::store handles its own validation or request parsing
        $jobId = JobController::store($request); 
        
        //$jobId = $job instanceof Job ? $job->id : $job;

        foreach ($validated['items'] as $itemData) {
            $createdItems[] = JobItem::create([
                'job_id'               => $jobId,
                'title'                => $itemData['title'],
                'receiver_name'        => $itemData['receiver_name'] ?? null,
                'description'          => $itemData['description'] ?? null,
                'pickup_address'       => $itemData['pickup_address'],
                'pickup_lat'           => $itemData['pickup_lat'] ?? null,
                'pickup_lng'           => $itemData['pickup_lng'] ?? null,
                'dropoff_lat'          => $itemData['dropoff_lat'] ?? null,
                'dropoff_lng'          => $itemData['dropoff_lng'] ?? null,
                'dropoff_address'      => $itemData['dropoff_address'],
                'mobility_type_needed' => $itemData['mobility_type_needed'] ?? null,
                'price'                => $itemData['price'] ?? null,
                'price_type'           => $itemData['price_type'] ?? 'fixed',
                'status'               => 'open',
                'posted_at'            => now(),
            ]);
        }
    });

    return response()->json([
        'success' => true,
        'message' => 'Job items created successfully.',
        'data'    => $createdItems,
    ], 201);
}

    // =========================================================================
    // SHOW — Get a single job item
    // GET /job-items/{jobItem}
    // =========================================================================
    public function show(JobItem $jobItem): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Job item retrieved successfully.',
            'data'    => $jobItem,
        ], 200);
    }


    // =========================================================================
    // UPDATE — Update a job item
    // PUT /job-items/{jobItem}
    // =========================================================================
    public function update(Request $request, JobItem $jobItem): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'job_id'               => ['prohibited'],
            'status'               => ['prohibited'],
            'posted_at'            => ['prohibited'],
            'title'                => ['sometimes', 'string', 'max:255'],
            'receiver_name'        => ['nullable', 'string', 'max:255'],
            'description'          => ['nullable', 'string', 'max:5000'],
            'pickup_address'       => ['sometimes', 'string', 'max:500'],
            'pickup_lat'           => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_lng'           => ['nullable', 'numeric', 'between:-180,180'],
            'dropoff_address'      => ['sometimes', 'string', 'max:500'],
            'dropoff_lat'          => ['nullable', 'numeric', 'between:-90,90'],
            'dropoff_lng'          => ['nullable', 'numeric', 'between:-180,180'],
            'mobility_type_needed' => ['nullable', 'string', Rule::in(['bike', 'van'])],
            'price'                => ['nullable', 'numeric', 'min:0'],
            'platform_fee'         => ['nullable', 'numeric', 'min:0'],
            'order_fee'            => ['nullable', 'numeric', 'min:0'],
            'total_fair_fee'       => ['nullable', 'numeric', 'min:0'],
            'price_type'           => ['sometimes', 'string', Rule::in(['fixed', 'negotiable'])],
            'expires_at'           => ['nullable', 'date', 'after:now'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $jobItem->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Job item updated successfully.',
            'data'    => $jobItem->fresh(),
        ], 200);
    }

    // =========================================================================
    // DESTROY — Delete a job item
    // DELETE /job-items/{jobItem}
    // =========================================================================
    public function destroy(JobItem $jobItem): JsonResponse
    {
        $jobItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job item deleted successfully.',
            'data'    => null,
        ], 200);
    }

    // =========================================================================
    // UPDATE STATUS — Change job item status
    // PATCH /job-items/{jobItem}/status
    // =========================================================================
    public function updateStatus(Request $request, JobItem $jobItem): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'string',
                         Rule::in(['open', 'matched', 'in_progress', 'completed', 'cancelled'])],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $data = ['status' => $request->status];

        if ($request->status === 'completed') {
            $data['delivered_at'] = now();
        }

        $jobItem->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Job item status updated successfully.',
            'data'    => $jobItem->fresh(),
        ], 200);
    }


}
