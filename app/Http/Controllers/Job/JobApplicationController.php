<?php

namespace App\Http\Controllers\Job;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class JobApplicationController extends Controller
{
    
     // =========================================================================
    // INDEX — List all applications (admin)
    // GET /api/job-applications
    // Filters: ?status=pending&job_id=3&user_rider_id=5
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $query = JobApplication::with([
            'job:id,title,status,price,user_id',
            'userRider:id,name,email',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('job_id')) {
            $query->where('job_id', $request->job_id);
        }

        if ($request->filled('user_rider_id')) {
            $query->where('user_rider_id', $request->user_rider_id);
        }

        $perPage      = min((int) $request->get('per_page', 15), 100);
        $applications = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $applications,
        ]);
    }

    // =========================================================================
    // STORE — Rider applies for a job
    // POST /api/jobs/{job}/applications
    // =========================================================================
    public function store(Request $request, Job $job): JsonResponse
    {
        // Job must be open to accept applications
        if ($job->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'This job is no longer accepting applications.',
            ], 422);
        }

        // Rider cannot apply to the same job twice
        $alreadyApplied = JobApplication::where('job_id', $job->id)
                                        ->where('user_rider_id', $request->user()->id)
                                        ->exists();

        if ($alreadyApplied) {
            return response()->json([
                'success' => false,
                'message' => 'You have already applied for this job.',
            ], 422);
        }

        // Job owner cannot apply to their own job
        if ($job->user_id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot apply for your own job.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'msg'       => ['nullable', 'string', 'max:1000'],
            'bid_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $application = JobApplication::create([
            'job_id'        => $job->id,
            'user_rider_id' => $request->user()->id,
            'msg'           => $request->msg,
            'bid_price'     => $request->bid_price,
            'status'        => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully.',
            'data'    => $application->load([
                'job:id,title,status,price,user_id',
                'userRider:id,name,email',
            ]),
        ], 201);
    }

    // =========================================================================
    // SHOW — View a single application
    // GET /api/job-applications/{jobApplication}
    // =========================================================================
    public function show(Request $request, JobApplication $jobApplication): JsonResponse
    {
        $user    = $request->user();
        $isAdmin = $user->hasRole('admin');
        $isOwner = $jobApplication->user_rider_id === $user->id;

        // Job poster can also view applications on their job
        $isJobPoster = $jobApplication->job->user_id === $user->id;

        if (! $isAdmin && ! $isOwner && ! $isJobPoster) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorised to view this application.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => $jobApplication->load([
                'job:id,title,status,price,user_id',
                'userRider:id,name,email',
            ]),
        ]);
    }

    // =========================================================================
    // BY JOB — All applications for a specific job (job owner + admin)
    // GET /api/jobs/{job}/applications
    // =========================================================================
    public function byJob(Request $request, Job $job): JsonResponse
    {
        $user    = $request->user();
        $isAdmin = $user->hasRole('admin');
        $isOwner = $job->user_id === $user->id;

        if (! $isAdmin && ! $isOwner) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorised to view applications for this job.',
            ], 403);
        }

        $query = $job->applications()
                     ->with('userRider:id,name,email');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage      = min((int) $request->get('per_page', 15), 50);
        $applications = $query->latest()->paginate($perPage);

        $summary = [
            'total'    => $job->applications()->count(),
            'pending'  => $job->applications()->where('status', 'pending')->count(),
            'accepted' => $job->applications()->where('status', 'accepted')->count(),
            'rejected' => $job->applications()->where('status', 'rejected')->count(),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'data'    => $applications,
        ]);
    }

    // =========================================================================
    // MY APPLICATIONS — All applications by the authenticated rider
    // GET /api/job-applications/mine
    // =========================================================================
    public function myApplications(Request $request): JsonResponse
    {
        $query = JobApplication::with('job:id,title,status,price,user_id')
                               ->where('user_rider_id', $request->user()->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage      = min((int) $request->get('per_page', 15), 50);
        $applications = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $applications,
        ]);
    }

    // =========================================================================
    // UPDATE — Rider edits their pending application (msg or bid_price only)
    // PUT /api/job-applications/{jobApplication}
    // =========================================================================
    public function update(Request $request, JobApplication $jobApplication): JsonResponse
    {
        // Only the applicant can edit
        if ($jobApplication->user_rider_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit your own applications.',
            ], 403);
        }

        // Can only edit while still pending
        if ($jobApplication->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending applications can be edited.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'msg'       => ['sometimes', 'nullable', 'string', 'max:1000'],
            'bid_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $jobApplication->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Application updated successfully.',
            'data'    => $jobApplication->fresh([
                'job:id,title,status,price,user_id',
                'userRider:id,name,email',
            ]),
        ]);
    }

    // =========================================================================
    // CHANGE STATUS — Job poster accepts or rejects an application
    // PATCH /api/job-applications/{jobApplication}/status
    // =========================================================================
    public function changeStatus(Request $request, JobApplication $jobApplication): JsonResponse
    {
        $user    = $request->user();
        $isAdmin = $user->hasRole('admin');
        $isJobPoster = $jobApplication->job->user_id === $user->id;

        if (! $isAdmin && ! $isJobPoster) {
            return response()->json([
                'success' => false,
                'message' => 'Only the job poster or an admin can accept or reject applications.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'string', Rule::in(['accepted', 'rejected', 'pending'])],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Accepting one application auto-rejects all others on the same job
        if ($request->status === 'accepted') {
            $alreadyAccepted = JobApplication::where('job_id', $jobApplication->job_id)
                                             ->where('status', 'accepted')
                                             ->where('id', '!=', $jobApplication->id)
                                             ->exists();

            if ($alreadyAccepted) {
                return response()->json([
                    'success' => false,
                    'message' => 'This job already has an accepted application.',
                ], 422);
            }

            // Reject all other pending applications for this job
            JobApplication::where('job_id', $jobApplication->job_id)
                          ->where('id', '!=', $jobApplication->id)
                          ->where('status', 'pending')
                          ->update(['status' => 'rejected']);
        }

        $oldStatus = $jobApplication->status;
        $jobApplication->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => "Application status changed from [{$oldStatus}] to [{$request->status}].",
            'data'    => $jobApplication->fresh(),
        ]);
    }

    // =========================================================================
    // WITHDRAW — Rider withdraws their own application
    // PATCH /api/job-applications/{jobApplication}/withdraw
    // =========================================================================
    public function withdraw(Request $request, JobApplication $jobApplication): JsonResponse
    {
        if ($jobApplication->user_rider_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only withdraw your own applications.',
            ], 403);
        }

        if (! in_array($jobApplication->status, ['pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending applications can be withdrawn.',
            ], 422);
        }

        $jobApplication->update(['status' => 'withdrawn']);

        return response()->json([
            'success' => true,
            'message' => 'Application withdrawn successfully.',
            'data'    => $jobApplication->fresh(),
        ]);
    }

    // =========================================================================
    // DESTROY — Hard-delete an application (admin only)
    // DELETE /api/job-applications/{jobApplication}
    // =========================================================================
    public function destroy(JobApplication $jobApplication): JsonResponse
    {
        $jobApplication->delete();

        return response()->json([
            'success' => true,
            'message' => 'Application deleted successfully.',
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


}
