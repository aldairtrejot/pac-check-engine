<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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
                'captcha_token' => trim((string) $request->input('captcha_token')),
            ]);

            $validated = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
                'captcha_token' => ['required', 'string'],
            ], [
                'email.required' => 'El correo es obligatorio.',
                'email.email' => 'Debes ingresar un correo válido.',
                'password.required' => 'La contraseña es obligatoria.',
                'captcha_token.required' => 'Debes completar el captcha.',
            ]);

            $key = 'login:' . mb_strtolower($validated['email']) . '|' . $request->ip();

            if (RateLimiter::tooManyAttempts($key, 10)) {
                $seconds = RateLimiter::availableIn($key);

                return response()->json([
                    'status' => false,
                    'message' => __('default.rate_limiter_message') . " ({$seconds}s)",
                ], 429);
            }

            if (!$this->isRecaptchaConfigured()) {
                return response()->json([
                    'status' => false,
                    'message' => 'El captcha no está configurado en el servidor.',
                ], 500);
            }

            if (!$this->verifyRecaptcha($validated['captcha_token'], $request->ip())) {
                return response()->json([
                    'status' => false,
                    'errors' => [
                        'captcha_token' => ['No se pudo validar el captcha. Inténtalo nuevamente.'],
                    ],
                ], 422);
            }

            if (!Auth::guard('web')->attempt([
                'email' => $validated['email'],
                'password' => $validated['password'],
            ], $request->boolean('remember'))) {
                RateLimiter::hit($key, 60);

                return response()->json([
                    'status' => false,
                    'message' => __('default.login_failure_message'),
                ], 200);
            }

            RateLimiter::clear($key);

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
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Verifica si reCAPTCHA está configurado
     */
    private function isRecaptchaConfigured(): bool
    {
        return filled(config('services.recaptcha.site_key'))
            && filled(config('services.recaptcha.secret_key'));
    }

    /**
     * Verifica el token de Google reCAPTCHA
     */
    private function verifyRecaptcha(string $token, ?string $ip = null): bool
    {
        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => config('services.recaptcha.secret_key'),
                    'response' => $token,
                    'remoteip' => $ip,
                ]);

            if (!$response->successful()) {
                return false;
            }

            return (bool) data_get($response->json(), 'success', false);
        } catch (\Throwable $th) {
            return false;
        }
    }
}