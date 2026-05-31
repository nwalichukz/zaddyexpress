<?php

namespace App\Http\Controllers\Rider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RiderProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RiderProfileController extends Controller
{
    
    // =========================================================================
    // INDEX — List all rider profiles (admin)
    // GET /api/riders
    // Supports filters: ?status=active&service_zone=Lagos&mobility_type=bike
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $query = RiderProfile::with('user:id,name,email');

        // ── Filters ──────────────────────────────────────────────────────────
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('service_zone')) {
            $query->where('service_zone', $request->service_zone);
        }
        if ($request->filled('mobility_type')) {
            $query->where('mobility_type', $request->mobility_type);
        }
        if ($request->filled('is_available')) {
            $query->where('is_available', $request->is_available);
        }
        if ($request->filled('state')) {
            $query->where('state', $request->state);
        }
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        // ── Sorting ───────────────────────────────────────────────────────────
        $sortBy       =  $request->get('sort_by', 'created_at');
        $sortOrder    =  $request->get('sort_order', 'desc');
        $allowedSorts =  ['created_at', 'legal_name', 'total_trips', 'review_rank'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $riders  = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $riders,
        ]);
    }
   
   // =========================================================================
    // STORE — Create a new rider profile
    // POST /api/riders
    // =========================================================================
    public function store(Request $request)
    {   return $request->all();
        $validator = Validator::make($request->all(), [
            'user_id'       => ['required', 'integer', 'exists:users,id',
                                Rule::unique('rider_profiles', 'user_id')],
            'legal_name'    => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'string', 'max:20',
                                Rule::unique('rider_profiles', 'mobile_number')],
            'service_zone'  => ['required', 'string', 'max:255'],
            'nin'           => ['nullable', 'string', 'max:11',
                                Rule::unique('rider_profiles', 'nin')],
            'gender'        => ['required', 'string', Rule::in(['male', 'female', 'other'])],
            'state'         => ['required', 'string', 'max:100'],
           // 'is_available'  => ['required', 'string', Rule::in(['yes', 'no'])],
            'mobility_type' => ['required', 'string', Rule::in(['bike', 'van'])],
            'plate_number'  => ['required', 'string', 'max:20',
                                Rule::unique('rider_profiles', 'plate_number')],
            'image'         => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')
                                     ->store('rider_profiles', 'public');
        }

        // Enforce safe server-side defaults — never trust client for these
        $data['status']      = 'inactive';
        $data['total_trips'] = '0';
        $data['is_available'] ='no';

        $riderProfile = RiderProfile::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Rider profile created successfully.',
            'data'    => $riderProfile->load('user:id,name,email'),
        ], 201);
    }

    // =========================================================================
    // SHOW — Get a single rider profile
    // GET /api/riders/{riderProfile}
    // =========================================================================
    public function show(RiderProfile $riderProfile): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $riderProfile->load('user:id,name,email'),
        ]);
    }

    // =========================================================================
    // MY PROFILE — Authenticated rider views their own profile
    // GET /api/rider/me
    // =========================================================================
    public function myProfile(Request $request): JsonResponse
    {
        $profile = RiderProfile::with('user:id,name,email')
                               ->where('user_id', $request->user()->id)
                               ->first();

        if (! $profile) {
            return response()->json([
                'success' => false,
                'message' => 'Rider profile not found for this account.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $profile,
        ]);
    }

    // =========================================================================
    // UPDATE — Update rider profile details
    // PUT /api/riders/{riderProfile}
    // =========================================================================
    public function update(Request $request, RiderProfile $riderProfile): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'legal_name'    => ['sometimes', 'string', 'max:255'],
            'mobile_number' => ['sometimes', 'string', 'max:20',
                                Rule::unique('rider_profiles', 'mobile_number')
                                    ->ignore($riderProfile->id)],
            'service_zone'  => ['sometimes', 'string', 'max:255'],
            'nin'           => ['nullable', 'string', 'max:11',
                                Rule::unique('rider_profiles', 'nin')
                                    ->ignore($riderProfile->id)],
            'gender'        => ['sometimes', 'string', Rule::in(['male', 'female', 'other'])],
            'state'         => ['sometimes', 'string', 'max:100'],
            'mobility_type' => ['sometimes', 'string', Rule::in(['bike', 'van', 'bicycle'])],
            'plate_number'  => ['sometimes', 'string', 'max:20',
                                Rule::unique('rider_profiles', 'plate_number')
                                    ->ignore($riderProfile->id)],
            'image'         => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            if ($riderProfile->image) {
                Storage::disk('public')->delete($riderProfile->image);
            }
            $data['image'] = $request->file('image')
                                     ->store('rider_profiles', 'public');
        }

        $riderProfile->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Rider profile updated successfully.',
            'data'    => $riderProfile->fresh('user:id,name,email'),
        ]);
    }


    // =========================================================================
    // UPDATE LOCATION — Rider pings current GPS coordinates
    // PATCH /api/riders/{riderProfile}/location
    // =========================================================================
    public function updateLocation(Request $request, RiderProfile $riderProfile): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_lat' => ['required', 'numeric', 'between:-90,90'],
            'current_lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $riderProfile->update([
            'current_lat' => $request->current_lat,
            'current_lng' => $request->current_lng,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location updated.',
            'data'    => [
                'current_lat' => $riderProfile->current_lat,
                'current_lng' => $riderProfile->current_lng,
                'updated_at'  => $riderProfile->updated_at,
            ],
        ]);
    }


    // =========================================================================
    // TOGGLE AVAILABILITY — Rider goes online / offline
    // PATCH /api/riders/{riderProfile}/availability
    // =========================================================================
    public function toggleAvailability(RiderProfile $riderProfile): JsonResponse
    {
        if ($riderProfile->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Only active riders can change their availability.',
            ], 403);
        }

        $newAvailability = $riderProfile->is_available === 'yes' ? 'no' : 'yes';

        $riderProfile->update(['is_available' => $newAvailability]);

        return response()->json([
            'success'      => true,
            'message'      => 'Availability updated.',
            'is_available' => $newAvailability,
        ]);
    }


    // =========================================================================
    // CHANGE STATUS — Admin sets active / inactive / suspended / banned
    // PATCH /api/riders/{riderProfile}/status
    // =========================================================================
    public function changeStatus(Request $request, RiderProfile $riderProfile): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'string',
                         Rule::in(['active', 'inactive', 'suspended', 'banned'])],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $oldStatus  = $riderProfile->status;
        $newStatus  = $request->status;
        $updateData = ['status' => $newStatus];

        // Force offline when deactivating/suspending/banning
        if (in_array($newStatus, ['inactive', 'suspended', 'banned'])) {
            $updateData['is_available'] = 'no';
        }

        $riderProfile->update($updateData);

        return response()->json([
            'success' => true,
            'message' => "Rider status changed from [{$oldStatus}] to [{$newStatus}].",
            'data'    => $riderProfile->fresh(),
        ]);
    }


    // =========================================================================
    // INCREMENT TRIPS — Called server-side after a trip completes
    // PATCH /api/riders/{riderProfile}/trips/increment
    // =========================================================================
    public function incrementTrips(RiderProfile $riderProfile): JsonResponse
    {
        $riderProfile->increment('total_trips');

        return response()->json([
            'success'     => true,
            'message'     => 'Trip count incremented.',
            'total_trips' => $riderProfile->fresh()->total_trips,
        ]);
    }


    // =========================================================================
    // UPDATE REVIEW RANK — Update rider rating after a trip
    // PATCH /api/riders/{riderProfile}/review-rank
    // =========================================================================
    public function updateReviewRank(Request $request, RiderProfile $riderProfile): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'review_rank' => ['required', 'numeric', 'min:0', 'max:5'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $riderProfile->update(['review_rank' => $request->review_rank]);

        return response()->json([
            'success'     => true,
            'message'     => 'Review rank updated.',
            'review_rank' => $riderProfile->review_rank,
        ]);
    }


    // =========================================================================
    // AVAILABLE IN ZONE — Find available riders for dispatch
    // GET /api/riders/available?service_zone=Enugu&mobility_type=bike
    // =========================================================================
    public function availableInZone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_zone'  => ['required', 'string'],
            'mobility_type' => ['nullable', 'string', Rule::in(['bike', 'van'])],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $query = RiderProfile::with('user:id,name,email')
                             ->where('service_zone', $request->service_zone)
                             ->where('status', 'active')
                             ->where('is_available', 'yes');

        if ($request->filled('mobility_type')) {
            $query->where('mobility_type', $request->mobility_type);
        }

        // Best-ranked riders first
        $riders = $query->orderByDesc('review_rank')->get();

        return response()->json([
            'success' => true,
            'count'   => $riders->count(),
            'data'    => $riders,
        ]);
    }


    // =========================================================================
    // DESTROY — Hard-delete a rider profile (admin)
    // DELETE /api/riders/{riderProfile}
    // =========================================================================
    public function destroy(RiderProfile $riderProfile): JsonResponse
    {
        if ($riderProfile->image) {
            Storage::disk('public')->delete($riderProfile->image);
        }

        $riderProfile->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rider profile deleted successfully.',
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
