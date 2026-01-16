<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthLoginController extends Controller
{
    /**
     * Inicio de sesión (session guard web)
     */
    public function authLogin(Request $request): JsonResponse
    {
        try {
            // sanitizar inputs (simple y seguro)
            $request->merge([
                'email' => trim((string) $request->input('email')),
                'password' => (string) $request->input('password'),
            ]);

            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);

            // Rate limiter por email + ip
            $key = 'login:' . mb_strtolower($credentials['email']) . '|' . $request->ip();

            if (RateLimiter::tooManyAttempts($key, 10)) {
                return response()->json([
                    'status' => false,
                    'message' => __('default.rate_limiter_message'),
                ], 429);
            }

            // ✅ Auth::attempt usa el provider de config/auth.php
            // y ahora provider apunta a App\Models\User (administracion.users)
            if (!Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
                RateLimiter::hit($key, 60);
                return response()->json([
                    'status' => false,
                    'message' => __('default.login_failure_message'),
                ], 200);
            }

            RateLimiter::clear($key);

            // ✅ muy importante para sesión
            $request->session()->regenerate();

            return response()->json([
                'status' => true,
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $th) {
            // \Log::error($th); // descomenta si quieres ver el error exacto
            return response()->json([
                'status' => false,
                'message' => __('default.error_message'),
            ], 200);
        }
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
