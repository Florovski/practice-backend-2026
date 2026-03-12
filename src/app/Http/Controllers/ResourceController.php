<?php
namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public function index(Request $request)
    {
        $query = Resource::where('is_active', true)
            ->withAvg('reviews', 'rating');

        // по типу
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // по минимальной вместимости
        if ($request->filled('capacity')) {
            $query->where('capacity', '>=', $request->capacity);
        }

        // по этажу
        if ($request->filled('floor')) {
            $query->where('floor', $request->floor);
        }

        // по максимальной цене
        if ($request->filled('max_price')) {
            $query->where('price_per_hour', '<=', $request->max_price);
        }

        // поиск свободных на конкретное время
        if ($request->filled('date') && $request->filled('start_time') && $request->filled('end_time')) {
            $query->whereDoesntHave('bookings', function ($q) use ($request) {
                $q->where('date', $request->date)
                  ->where('status', 'active')
                  ->where('start_time', '<', $request->end_time)
                  ->where('end_time', '>', $request->start_time);
            });
        }

        // сортировка
        $sortBy  = in_array($request->get('sort_by'), ['price_per_hour', 'capacity']) 
                   ? $request->get('sort_by') : 'id';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortBy, $sortDir);

        // пагинация
        $perPage = min((int) $request->get('per_page', 10), 50);
        $resources = $query->paginate($perPage);

        return response()->json($resources);
    }

    public function show(Resource $resource)
    {
        $resource->loadAvg('reviews', 'rating');
        return response()->json($resource);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'type'           => 'required|string',
            'capacity'       => 'required|integer|min:1',
            'floor'          => 'required|integer|min:1',
            'price_per_hour' => 'required|numeric|min:0',
            'description'    => 'nullable|string',
        ]);

        $resource = Resource::create($request->all());
        return response()->json($resource, 201);
    }

    public function update(Request $request, Resource $resource)
    {
        $request->validate([
            'name'           => 'sometimes|string|max:255',
            'type'           => 'sometimes|string',
            'capacity'       => 'sometimes|integer|min:1',
            'floor'          => 'sometimes|integer|min:1',
            'price_per_hour' => 'sometimes|numeric|min:0',
            'description'    => 'nullable|string',
            'is_active'      => 'sometimes|boolean',
        ]);

        $resource->update($request->all());
        return response()->json($resource);
    }

    public function destroy(Resource $resource)
    {
        $resource->delete();
        return response()->json(['message' => 'Зал удалён']);
    }
}
