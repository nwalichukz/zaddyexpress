<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\RiderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase0ApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_registration_returns_mobile_contract_session(): void
    {
        $response = $this->postJson('/api/user/create', [
            'name' => 'Phase Zero Customer',
            'email' => 'phase0.customer@gmail.com',
            'password' => 'password123',
            'mobile_number' => '08012345678',
            'user_type' => 'user',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'mobile_number', 'user_type'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'phase0.customer@gmail.com',
            'mobile_number' => '08012345678',
            'user_type' => 'user',
        ]);
    }

    public function test_customer_login_returns_jwt_and_user_payload(): void
    {
        User::factory()->create([
            'email' => 'phase0.login@gmail.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'user_type' => 'user',
        ]);

        $this->postJson('/api/user/login', [
            'email' => 'phase0.login@gmail.com',
            'password' => 'password123',
            'user_type' => 'user',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_rider_login_rejects_customer_credentials(): void
    {
        User::factory()->create([
            'email' => 'phase0.not-rider@gmail.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'user_type' => 'user',
        ]);

        $this->postJson('/api/rider/login', [
            'email' => 'phase0.not-rider@gmail.com',
            'password' => 'password123',
            'user_type' => 'rider',
        ])->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_current_user_endpoint_requires_jwt_authentication(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_inactive_rider_cannot_toggle_availability(): void
    {
        $rider = User::factory()->create([
            'user_type' => 'rider',
        ]);

        $profile = RiderProfile::create([
            'user_id' => $rider->id,
            'legal_name' => 'Phase Zero Rider',
            'mobile_number' => '08087654321',
            'service_zone' => 'Lagos',
            'nin' => '12345678901',
            'gender' => 'male',
            'state' => 'Lagos',
            'mobility_type' => 'bike',
            'plate_number' => 'ABC-123XY',
            'status' => 'inactive',
            'total_trips' => '0',
            'is_available' => 'no',
        ]);

        $this->actingAs($rider, 'api')
            ->patchJson("/api/riders/{$profile->id}/availability", [
                'current_latitude' => 6.5244,
                'current_longitude' => 3.3792,
            ])->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_authenticated_customer_can_create_and_list_jobs(): void
    {
        $customer = User::factory()->create([
            'user_type' => 'user',
        ]);

        $this->actingAs($customer, 'api')
            ->postJson('/api/job/create', [
                'user_id' => $customer->id,
                'title' => 'Phase Zero Delivery',
                'pickup_address' => 'Ikeja',
                'dropoff_address' => 'Lekki',
                'mobility_type_needed' => 'bike',
                'price' => 3500,
                'price_type' => 'fixed',
            ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', $customer->id);

        $this->getJson('/api/jobs')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);

        $this->actingAs($customer, 'api')
            ->getJson("/api/my-jobs/{$customer->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        $this->assertDatabaseHas('jobs', [
            'user_id' => $customer->id,
            'title' => 'Phase Zero Delivery',
        ]);
    }
}
