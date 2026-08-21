<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\UserActionLogger;
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
            $request->merge([
                'email' => trim((string) $request->input('email')),
                'password' => (string) $request->input('password'),
                'captcha' => trim((string) $request->input('captcha')),
            ]);

            $validated = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
                'captcha' => ['required', 'captcha'],
            ], [
                'email.required' => 'El correo es obligatorio.',
                'email.email' => 'Debes ingresar un correo válido.',
                'password.required' => 'La contraseña es obligatoria.',
                'captcha.required' => 'Debes escribir el captcha.',
                'captcha.captcha' => 'El captcha es incorrecto.',
            ]);

            $key = 'login:' . mb_strtolower($validated['email']) . '|' . $request->ip();

            if (RateLimiter::tooManyAttempts($key, 10)) {
                $seconds = RateLimiter::availableIn($key);

                UserActionLogger::fromRequest(
                    request: $request,
                    idUsuario: null,
                    modulo: 'AUTENTICACION',
                    accion: 'LOGIN_BLOQUEADO',
                    descripcion: 'Intento de inicio de sesión bloqueado por demasiados intentos.',
                    payload: [
                        'email' => $validated['email'],
                        'available_in_seconds' => $seconds,
                    ]
                );

                return response()->json([
                    'status' => false,
                    'message' => __('default.rate_limiter_message') . " ({$seconds}s)",
                ], 429);
            }

            if (!Auth::guard('web')->attempt([
                'email' => $validated['email'],
                'password' => $validated['password'],
                'status' => true,
            ], $request->boolean('remember'))) {
                RateLimiter::hit($key, 60);

                UserActionLogger::fromRequest(
                    request: $request,
                    idUsuario: null,
                    modulo: 'AUTENTICACION',
                    accion: 'LOGIN_FALLIDO',
                    descripcion: 'Credenciales inválidas o cuenta inactiva.',
                    payload: [
                        'email' => $validated['email'],
                    ]
                );

                return response()->json([
                    'status' => false,
                    'message' => __('default.login_failure_message'),
                ], 200);
            }

            RateLimiter::clear($key);

            $request->session()->regenerate();

            UserActionLogger::fromRequest(
                request: $request,
                idUsuario: Auth::id() ? (int) Auth::id() : null,
                modulo: 'AUTENTICACION',
                accion: 'LOGIN_EXITOSO',
                descripcion: 'Inicio de sesión correcto.',
                payload: [
                    'email' => $validated['email'],
                    'remember' => $request->boolean('remember'),
                ]
            );

            return response()->json([
                'status' => true,
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => __('default.error_message'),
            ], 200);
        }
    }

    /**
     * Devuelve un nuevo captcha
     */
    public function captcha(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'captcha_src' => captcha_src('flat'),
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        UserActionLogger::fromRequest(
            request: $request,
            idUsuario: Auth::id() ? (int) Auth::id() : null,
            modulo: 'AUTENTICACION',
            accion: 'LOGOUT',
            descripcion: 'Cierre de sesión.',
            payload: [
                'email' => Auth::user()?->email,
            ]
        );

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
