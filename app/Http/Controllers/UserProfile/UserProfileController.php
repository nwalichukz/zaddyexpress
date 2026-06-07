<?php

namespace App\Http\Controllers\UserProfile;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{
    // =========================================================================
    // INDEX — List all user profiles (admin only)
    // GET /api/user-profiles
    // Filters: ?gender=male&search=John
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $query = UserProfile::with('user:id,name,email');

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sur_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('other_name', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }

        $perPage  = min((int) $request->get('per_page', 15), 100);
        $profiles = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $profiles,
        ]);
    }

    // =========================================================================
    // STORE — Create a user profile
    // POST /api/user-profiles
    // =========================================================================
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id'      => ['required', 'integer', 'exists:users,id',
                               Rule::unique('user_profiles', 'user_id')],
            'sur_name'     => ['required', 'string', 'max:255'],
            'last_name'    => ['required', 'string', 'max:255'],
            'other_name'   => ['nullable', 'string', 'max:255'],
            'mobile_number'=> ['required', 'string', 'max:20',
                               Rule::unique('user_profiles', 'mobile_number')],
            'gender'       => ['required', 'string', Rule::in(['male', 'female', 'other'])],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $profile = UserProfile::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'User profile created successfully.',
            'data'    => $profile->load('user:id,name,email'),
        ], 201);
    }

    // =========================================================================
    // SHOW — View any single user profile (admin)
    // GET /api/user-profiles/{userProfile}
    // =========================================================================
    public function show(UserProfile $userProfile): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $userProfile->load('user:id,name,email'),
        ]);
    }

    // =========================================================================
    // MY PROFILE — Authenticated user views their own profile
    // GET /api/user-profiles/me
    // =========================================================================
    public function myProfile(Request $request): JsonResponse
    {
        $profile = UserProfile::with('user:id,name,email')
                              ->where('user_id', $request->user()->id)
                              ->first();

        if (! $profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found. Please create your profile.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $profile,
        ]);
    }

    // =========================================================================
    // UPDATE — Edit a user profile
    // PUT /api/user-profiles/{userProfile}
    // =========================================================================
    public function update(Request $request, UserProfile $userProfile): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sur_name'     => ['sometimes', 'string', 'max:255'],
            'last_name'    => ['sometimes', 'string', 'max:255'],
            'other_name'   => ['nullable', 'string', 'max:255'],
            'mobile_number'=> ['sometimes', 'string', 'max:20',
                               Rule::unique('user_profiles', 'mobile_number')
                                   ->ignore($userProfile->id)],
            'gender'       => ['sometimes', 'string', Rule::in(['male', 'female', 'other'])],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $userProfile->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data'    => $userProfile->fresh('user:id,name,email'),
        ]);
    }

    // =========================================================================
    // DESTROY — Delete a user profile (admin only)
    // DELETE /api/user-profiles/{userProfile}
    // =========================================================================
    public function destroy(UserProfile $userProfile): JsonResponse
    {
        $userProfile->delete();

        return response()->json([
            'success' => true,
            'message' => 'User profile deleted successfully.',
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
