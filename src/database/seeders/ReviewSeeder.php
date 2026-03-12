<?php
namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        // бронирование 1 - с отзывом (для теста среднего рейтинга)
        $booking1 = Booking::create([
            'user_id'     => 2,
            'resource_id' => 1,
            'date'        => now()->subDays(5)->format('Y-m-d'),
            'start_time'  => '10:00',
            'end_time'    => '12:00',
            'status'      => 'active',
        ]);
        Review::create([
            'user_id'     => 2,
            'booking_id'  => $booking1->id,
            'resource_id' => 1,
            'rating'      => 4,
            'comment'     => 'Хороший зал, всё работает!',
        ]);

        // бронирование 2 - с отзывом (для среднего рейтинга)
        $booking2 = Booking::create([
            'user_id'     => 2,
            'resource_id' => 1,
            'date'        => now()->subDays(3)->format('Y-m-d'),
            'start_time'  => '14:00',
            'end_time'    => '16:00',
            'status'      => 'active',
        ]);
        Review::create([
            'user_id'     => 2,
            'booking_id'  => $booking2->id,
            'resource_id' => 1,
            'rating'      => 3,
            'comment'     => 'Неплохо, но шумновато.',
        ]);

        // бронирование 3 - БЕЗ отзыва для 12 теста
        Booking::create([
            'user_id'     => 2,
            'resource_id' => 3,
            'date'        => now()->subDays(10)->format('Y-m-d'),
            'start_time'  => '14:00',
            'end_time'    => '16:00',
            'status'      => 'active',
        ]);
    }
}
