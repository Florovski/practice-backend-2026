<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Resource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CriticalApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_bookings_endpoint_requires_auth_token(): void
    {
        $response = $this->getJson('/api/bookings');

        $response->assertStatus(401);
    }

    public function test_authorized_user_can_access_bookings_endpoint(): void
    {
        $user = $this->createUser();

        $response = $this->getJson('/api/bookings', $this->authHeaders($user));

        $response
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_user_can_create_booking(): void
    {
        $user = $this->createUser();
        $resource = $this->createResource();

        $payload = [
            'resource_id' => $resource->id,
            'date' => Carbon::tomorrow()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
        ];

        $response = $this->postJson('/api/bookings', $payload, $this->authHeaders($user));

        $response
            ->assertCreated()
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonPath('resource_id', $resource->id)
            ->assertJsonPath('status', 'active');

        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'date' => $payload['date'],
            'status' => 'active',
        ]);
    }

    public function test_booking_with_time_overlap_is_rejected(): void
    {
        $user = $this->createUser();
        $resource = $this->createResource();

        Booking::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'date' => Carbon::tomorrow()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'status' => 'active',
        ]);

        $payload = [
            'resource_id' => $resource->id,
            'date' => Carbon::tomorrow()->format('Y-m-d'),
            'start_time' => '11:00',
            'end_time' => '13:00',
        ];

        $response = $this->postJson('/api/bookings', $payload, $this->authHeaders($user));

        $response
            ->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'conflict' => ['date', 'start_time', 'end_time'],
            ]);

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_regular_user_cannot_create_resource(): void
    {
        $user = $this->createUser('user');

        $payload = [
            'name' => 'Yoga Hall',
            'type' => 'yoga',
            'capacity' => 10,
            'floor' => 2,
            'price_per_hour' => 450,
        ];

        $response = $this->postJson('/api/resources', $payload, $this->authHeaders($user));

        $response->assertStatus(403);
    }

    public function test_admin_can_create_resource(): void
    {
        $admin = $this->createUser('admin');

        $payload = [
            'name' => 'Premium Hall',
            'type' => 'gym',
            'capacity' => 30,
            'floor' => 1,
            'price_per_hour' => 900,
            'description' => 'Large training zone',
        ];

        $response = $this->postJson('/api/resources', $payload, $this->authHeaders($admin));

        $response
            ->assertCreated()
            ->assertJsonPath('name', $payload['name'])
            ->assertJsonPath('type', $payload['type']);

        $this->assertDatabaseHas('resources', [
            'name' => $payload['name'],
            'type' => $payload['type'],
            'capacity' => $payload['capacity'],
        ]);
    }

    private function createUser(string $role = 'user'): User
    {
        return User::factory()->create([
            'role' => $role,
        ]);
    }

    private function createResource(): Resource
    {
        return Resource::create([
            'name' => 'Test Resource',
            'type' => 'gym',
            'capacity' => 15,
            'floor' => 1,
            'price_per_hour' => 500,
            'description' => 'Test description',
            'is_active' => true,
        ]);
    }

    private function authHeaders(User $user): array
    {
        $token = auth('api')->login($user);

        return [
            'Authorization' => 'Bearer ' . $token,
        ];
    }
}
