<?php
namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Booking;
use App\Models\Resource;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Resource $resource)
    {
        $reviews = Review::where('resource_id', $resource->id)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'average_rating' => $resource->average_rating,
            'reviews'        => $reviews,
        ]);
    }

    public function store(Request $request, Resource $resource)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'rating'     => 'required|integer|min:1|max:5',
            'comment'    => 'nullable|string|max:1000',
        ]);

        $user = auth('api')->user();

        // проверяем что бронирование принадлежит этому пользователю
        $booking = Booking::where('id', $request->booking_id)
            ->where('user_id', $user->id)
            ->where('resource_id', $resource->id)
            ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'Бронирование не найдено или не принадлежит вам.',
            ], 403);
        }

        // только после завершенного бронирования
        // считаем завершённым если дата+время окончания уже прошли
        $bookingEnd = \Carbon\Carbon::parse($booking->date . ' ' . $booking->end_time);

        if ($bookingEnd->isFuture()) {
            return response()->json([
                'message' => 'Отзыв можно оставить только после завершения бронирования.',
            ], 422);
        }

        // нельзя оставлять дважды отзыв (накрутка запрещена)
        $exists = Review::where('booking_id', $booking->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Вы уже оставили отзыв на это бронирование.',
            ], 422);
        }

        $review = Review::create([
            'user_id'     => $user->id,
            'booking_id'  => $booking->id,
            'resource_id' => $resource->id,
            'rating'      => $request->rating,
            'comment'     => $request->comment,
        ]);

        return response()->json([
            'message'        => 'Отзыв успешно добавлен.',
            'review'         => $review,
            'average_rating' => $resource->fresh()->average_rating,
        ], 201);
    }
}
