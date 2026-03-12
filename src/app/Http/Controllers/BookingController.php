<?php
namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    // юзер свои видит, админ все
    public function index()
    {
        $user = auth('api')->user();

        if ($user->isAdmin()) {
            $bookings = Booking::with(['user', 'resource'])->get();
        } else {
            $bookings = Booking::with(['resource'])
                ->where('user_id', $user->id)
                ->get();
        }

        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $request->validate([
            'resource_id' => 'required|exists:resources,id',
            'date'        => 'required|date|after_or_equal:today',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
        ]);

        $user = auth('api')->user();

        // проверка пересечений
        $conflict = Booking::where('resource_id', $request->resource_id)
            ->where('date', $request->date)
            ->where('status', 'active')
            ->where(function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    // новое начало попадает внутрь существующего
                    $q->where('start_time', '<', $request->end_time)
                      ->where('end_time', '>', $request->start_time);
                });
            })
            ->first();

        if ($conflict) {
            Log::warning('Конфликт бронирования', [
                'user_id'     => $user->id,
                'resource_id' => $request->resource_id,
                'date'        => $request->date,
                'start_time'  => $request->start_time,
                'end_time'    => $request->end_time,
                'conflict_id' => $conflict->id,
                'reason'      => 'Временной интервал пересекается с существующим бронированием',
            ]);

            return response()->json([
                'message' => 'Этот зал уже забронирован на выбранное время.',
                'conflict' => [
                    'date'       => $conflict->date,
                    'start_time' => $conflict->start_time,
                    'end_time'   => $conflict->end_time,
                ],
            ], 422);
        }

        $booking = Booking::create([
            'user_id'     => $user->id,
            'resource_id' => $request->resource_id,
            'date'        => $request->date,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'status'      => 'active',
        ]);

        Log::info('Бронирование создано', [
            'booking_id'  => $booking->id,
            'user_id'     => $user->id,
            'resource_id' => $booking->resource_id,
            'date'        => $booking->date,
            'start_time'  => $booking->start_time,
            'end_time'    => $booking->end_time,
        ]);

        return response()->json($booking->load('resource'), 201);
    }

    public function destroy($id)
    {
        $user    = auth('api')->user();
        $booking = Booking::findOrFail($id);

        // юзер только своё отменяет
        if (!$user->isAdmin() && $booking->user_id !== $user->id) {
            Log::warning('Попытка отменить чужое бронирование', [
                'initiator_id' => $user->id,
                'booking_id'   => $booking->id,
                'owner_id'     => $booking->user_id,
                'reason'       => 'Нет прав на отмену чужого бронирования',
            ]);

            return response()->json([
                'message' => 'Вы не можете отменить чужое бронирование.',
            ], 403);
        }

        // отмена уже отмененного (низя)
        if ($booking->status === 'cancelled') {
            return response()->json([
                'message' => 'Бронирование уже отменено.',
            ], 422);
        }

        $booking->update(['status' => 'cancelled']);

        Log::info('Бронирование отменено', [
            'booking_id'   => $booking->id,
            'initiator_id' => $user->id,
            'is_admin'     => $user->isAdmin(),
        ]);

        return response()->json(['message' => 'Бронирование успешно отменено.']);
    }
}
