<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auth\UserEntityModel;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthLoginController extends Controller
{
    /**
     * El metodo retorna el inicio de sesión de la aplicación
     */
    public function authLogin(Request $request): JsonResponse
    {
        try {
            // class
            $config = HTMLPurifier_Config::createDefault();
            $purifier = new HTMLPurifier($config);

            // sanitizar inputs
            $request->merge([
                'email' => $purifier->purify(trim($request->email)),
                'password' => $purifier->purify(trim($request->password)),
            ]);

            // rate limiter
            $key = 'login-attempts:'.$request->ip();

            if (RateLimiter::tooManyAttempts($key, 10)) {
                return response()->json([
                    'status' => false,
                    'message' => __('default.rate_limiter_message'),
                ], 200);
            }

            RateLimiter::hit($key);

            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = UserEntityModel::where('email', $credentials['email'])
                ->where('status', true)
                ->first();

            if (! $user || ! Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => __('default.login_failure_message'),
                ], 200);
            }

            Auth::login($user);

            return response()->json([
                'status' => true,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $th) {
            // \Log::info($th);

            return response()->json([
                'status' => false,
                'message' => __('default.error_message'),
            ], 200);
        }
    }

    /**
     * Cierra la sesión del usuario y lo redirige al login
     */
    public function logout(Request $request)
    {
        Auth::logout();                        // Cierra la sesión

        $request->session()->invalidate();     // Invalida la sesión actual
        $request->session()->regenerateToken();// Regenera el token CSRF

        return redirect()->route('login');     // Regresa a la pantalla de login
    }
}
