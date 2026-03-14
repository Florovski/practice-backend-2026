<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Resource;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleReviewFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_returns_active_bookings_for_day_period(): void
    {
        $user = $this->createUser();
        $resource = $this->createResource();
        $date = Carbon::today()->format('Y-m-d');

        Booking::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'date' => $date,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/resources/{$resource->id}/schedule?date={$date}&period=day");

        $response
            ->assertOk()
            ->assertJsonPath('period', 'day')
            ->assertJsonPath('from', $date)
            ->assertJsonPath('to', $date)
            ->assertJsonCount(1, 'schedule')
            ->assertJsonPath('schedule.0.booked_by', $user->name);
    }

    public function test_user_can_leave_review_after_booking_has_finished(): void
    {
        $user = $this->createUser();
        $resource = $this->createResource();

        $booking = Booking::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'date' => Carbon::yesterday()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'active',
        ]);

        $payload = [
            'booking_id' => $booking->id,
            'rating' => 5,
            'comment' => 'Great place',
        ];

        $response = $this->postJson(
            "/api/resources/{$resource->id}/reviews",
            $payload,
            $this->authHeaders($user)
        );

        $response
            ->assertCreated()
            ->assertJsonStructure(['message', 'review', 'average_rating']);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'resource_id' => $resource->id,
            'rating' => 5,
        ]);
    }

    public function test_user_cannot_leave_review_before_booking_has_finished(): void
    {
        $user = $this->createUser();
        $resource = $this->createResource();

        $booking = Booking::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'date' => Carbon::tomorrow()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'active',
        ]);

        $payload = [
            'booking_id' => $booking->id,
            'rating' => 4,
            'comment' => 'Too early',
        ];

        $response = $this->postJson(
            "/api/resources/{$resource->id}/reviews",
            $payload,
            $this->authHeaders($user)
        );

        $response->assertStatus(422);
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_cannot_leave_duplicate_review_for_same_booking(): void
    {
        $user = $this->createUser();
        $resource = $this->createResource();

        $booking = Booking::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'date' => Carbon::yesterday()->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'status' => 'active',
        ]);

        Review::create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'resource_id' => $resource->id,
            'rating' => 5,
            'comment' => 'First review',
        ]);

        $payload = [
            'booking_id' => $booking->id,
            'rating' => 3,
            'comment' => 'Second review attempt',
        ];

        $response = $this->postJson(
            "/api/resources/{$resource->id}/reviews",
            $payload,
            $this->authHeaders($user)
        );

        $response->assertStatus(422);
        $this->assertDatabaseCount('reviews', 1);
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
            'name' => 'Flow Resource',
            'type' => 'gym',
            'capacity' => 20,
            'floor' => 1,
            'price_per_hour' => 500,
            'description' => 'Flow test resource',
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
