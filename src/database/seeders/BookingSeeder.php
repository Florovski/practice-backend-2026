<?php
namespace Database\Seeders;

use App\Models\Booking;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        Booking::create([
            'user_id'     => 2,
            'resource_id' => 1,
            'date'        => now()->addDay()->format('Y-m-d'),
            'start_time'  => '10:00',
            'end_time'    => '12:00',
            'status'      => 'active',
        ]);

        Booking::create([
            'user_id'     => 2,
            'resource_id' => 2,
            'date'        => now()->addDay()->format('Y-m-d'),
            'start_time'  => '14:00',
            'end_time'    => '15:00',
            'status'      => 'active',
        ]);

        Booking::create([
            'user_id'     => 2,
            'resource_id' => 3,
            'date'        => now()->addDays(2)->format('Y-m-d'),
            'start_time'  => '09:00',
            'end_time'    => '11:00',
            'status'      => 'cancelled',
        ]);

        Booking::create([
            'user_id'     => 1,
            'resource_id' => 2,
            'date'        => now()->addDays(3)->format('Y-m-d'),
            'start_time'  => '16:00',
            'end_time'    => '18:00',
            'status'      => 'active',
        ]);
    }
}
