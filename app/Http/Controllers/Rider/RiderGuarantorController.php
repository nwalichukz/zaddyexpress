<?php

namespace App\Http\Controllers\Rider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Guarantor;
use App\Models\RiderProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RiderGuarantorController extends Controller
{
    // =========================================================================
    // INDEX — List all guarantors (admin)
    // GET /api/guarantors
    // Optional filters: ?state=Lagos&rider_profile_id=5
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $query = Guarantor::with('riderProfile:id,legal_name,mobile_number,status');

        if ($request->filled('state')) {
            $query->where('state', $request->state);
        }

        if ($request->filled('rider_profile_id')) {
            $query->where('rider_profile_id', $request->rider_profile_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mobile_no', 'like', "%{$search}%");
            });
        }

        $perPage    = min((int) $request->get('per_page', 15), 100);
        $guarantors = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $guarantors,
        ]);
    }

    // =========================================================================
    // STORE — Add a guarantor to a rider profile
    // POST /api/rider-profiles/{riderProfile}/guarantors
    // =========================================================================
    public function store(Request $request, RiderProfile $riderProfile): JsonResponse
    {
        // A rider should not have more than 2 guarantors
        if ($riderProfile->guarantors()->count() >= 2) {
            return response()->json([
                'success' => false,
                'message' => 'This rider already has the maximum of 2 guarantors.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'name'      => ['required', 'string', 'max:255'],
            'state'     => ['required', 'string', 'max:100'],
            'address'   => ['required', 'string', 'max:500'],
            'mobile_no' => ['required', 'string', 'digits:11',
                            Rule::unique('guarantors', 'mobile_no')],
            'nin'       => ['required', 'string', 'digits:11',
                            Rule::unique('guarantors', 'nin')],
            'image'     => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $data                    = $validator->validated();
        $data['rider_profile_id'] = $riderProfile->id;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')
                                     ->store('guarantors', 'public');
        }

        $guarantor = Guarantor::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Guarantor added successfully.',
            'data'    => $guarantor->load('riderProfile:id,legal_name'),
        ], 201);
    }

    // =========================================================================
    // SHOW — View a single guarantor
    // GET /api/guarantors/{guarantor}
    // =========================================================================
    public function show(Guarantor $guarantor): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $guarantor->load('riderProfile:id,legal_name,mobile_number,status'),
        ]);
    }

    // =========================================================================
    // BY RIDER — List all guarantors for a specific rider
    // GET /api/rider-profiles/{riderProfile}/guarantors
    // =========================================================================
    public function byRider(RiderProfile $riderProfile): JsonResponse
    {
        $guarantors = $riderProfile->guarantors()->latest()->get();

        return response()->json([
            'success' => true,
            'count'   => $guarantors->count(),
            'data'    => $guarantors,
        ]);
    }

    // =========================================================================
    // UPDATE — Edit a guarantor's details
    // PUT /api/guarantors/{guarantor}
    // =========================================================================
    public function update(Request $request, Guarantor $guarantor): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'      => ['sometimes', 'string', 'max:255'],
            'state'     => ['sometimes', 'string', 'max:100'],
            'address'   => ['sometimes', 'string', 'max:500'],
            'mobile_no' => ['sometimes', 'string', 'max:20',
                            Rule::unique('guarantors', 'mobile_no')
                                ->ignore($guarantor->id)],
            'nin'       => ['sometimes', 'prohibited'],

            'image'     => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            if ($guarantor->image) {
                Storage::disk('public')->delete($guarantor->image);
            }
            $data['image'] = $request->file('image')
                                     ->store('guarantors', 'public');
        }

        $guarantor->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Guarantor updated successfully.',
            'data'    => $guarantor->fresh('riderProfile:id,legal_name'),
        ]);
    }

    // =========================================================================
    // DESTROY — Delete a guarantor record
    // DELETE /api/guarantors/{guarantor}
    // =========================================================================
    public function destroy(Guarantor $guarantor): JsonResponse
    {
        if ($guarantor->image) {
            Storage::disk('public')->delete($guarantor->image);
        }

        $guarantor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Guarantor deleted successfully.',
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
