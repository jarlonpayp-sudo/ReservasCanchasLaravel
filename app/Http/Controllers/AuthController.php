<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => $validated['password'],
        ]);

        return response()->json([
            'exito' => true,
            'mensaje' => 'Usuario registrado correctamente.',
            'usuario' => $user->only(['id', 'name', 'email']),
        ], 201);
    }

    public function login(Request $request, JwtService $jwt): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', strtolower($validated['email']))->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas no son correctas.'],
            ]);
        }

        try {
            $token = $jwt->generate([
                'sub' => (string) $user->id,
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'exito' => false,
                'mensaje' => 'No fue posible generar el token de autenticación. Revisa la configuración JWT.',
            ], 500);
        }

        return response()->json([
            'exito' => true,
            'mensaje' => 'Inicio de sesión correcto.',
            'token_type' => 'Bearer',
            'access_token' => $token,
            'expires_in' => max(1, (int) config('jwt.ttl', 60)) * 60,
            'usuario' => $user->only(['id', 'name', 'email']),
        ]);
    }
}
