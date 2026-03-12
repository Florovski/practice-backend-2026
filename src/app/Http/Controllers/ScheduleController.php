<?php
namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Resource;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    // период день\неделя
    public function index(Request $request, Resource $resource)
    {
        $request->validate([
            'date'   => 'required|date',
            'period' => 'in:day,week',
        ]);

        $period = $request->get('period', 'day');
        $date   = Carbon::parse($request->date);

        if ($period === 'week') {
            $startDate = $date->copy()->startOfWeek();
            $endDate   = $date->copy()->endOfWeek();
        } else {
            $startDate = $date->copy();
            $endDate   = $date->copy();
        }

        $bookings = Booking::where('resource_id', $resource->id)
            ->where('status', 'active')
            ->whereBetween('date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
            ])
            ->with('user:id,name')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(function ($b) {
                return [
                    'date'       => $b->date,
                    'start_time' => $b->start_time,
                    'end_time'   => $b->end_time,
                    'booked_by'  => $b->user->name ?? 'Неизвестно',
                ];
            });

        return response()->json([
            'resource' => $resource->name,
            'period'   => $period,
            'from'     => $startDate->format('Y-m-d'),
            'to'       => $endDate->format('Y-m-d'),
            'schedule' => $bookings,
        ]);
    }
}
