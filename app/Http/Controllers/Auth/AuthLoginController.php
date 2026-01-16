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
    public function authLogin(Request $request): JsonResponse
    {
        try {
            $config = HTMLPurifier_Config::createDefault();
            $purifier = new HTMLPurifier($config);

            $request->merge([
                'email'    => $purifier->purify(trim((string) $request->email)),
                'password' => $purifier->purify(trim((string) $request->password)),
            ]);

            $key = 'login-attempts:' . $request->ip();

            if (RateLimiter::tooManyAttempts($key, 10)) {
                return response()->json([
                    'status'  => false,
                    'message' => __('default.rate_limiter_message'),
                ], 200);
            }

            RateLimiter::hit($key);

            $credentials = $request->validate([
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);

            // ✅ FIX: estatus (no status)
            $user = UserEntityModel::where('email', $credentials['email'])
                ->where('estatus', true)
                ->first();

            if (! $user || ! Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'status'  => false,
                    'message' => __('default.login_failure_message'),
                ], 200);
            }

            Auth::login($user);

            // ✅ evita problemas de sesión
            $request->session()->regenerate();

            // ✅ limpia intentos
            RateLimiter::clear($key);

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
                'status'  => false,
                'message' => __('default.error_message'),
            ], 200);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
