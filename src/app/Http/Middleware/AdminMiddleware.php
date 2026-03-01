<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth('api')->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Доступ запрещён. Только для администратора.'], 403);
        }

        return $next($request);
    }
}
