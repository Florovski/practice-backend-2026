<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Регистрация
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'user',
        ]);

        $token = auth('api')->login($user);

        return response()->json(['token' => $token, 'user' => $user], 201);
    }

    // Вход
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $token = auth('api')->attempt($request->only('email', 'password'));

        if (!$token) {
            return response()->json(['message' => 'Неверный email или пароль'], 401);
        }

        return response()->json(['token' => $token]);
    }

    // Выход
    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Выход выполнен']);
    }

    // Текущий пользователь
    public function me()
    {
        return response()->json(auth('api')->user());
    }
}
