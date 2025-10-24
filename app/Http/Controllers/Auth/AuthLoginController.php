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
     *
     * @return void
     */
    public function authLogin(Request $request): JsonResponse
    {
        try {
            // class
            $config = HTMLPurifier_Config::createDefault();
            $purifier = new HTMLPurifier($config);

            // object start modification}

            $request->merge([
                'email' => $purifier->purify(trim($request->email)),
                'password' => $purifier->purify(trim($request->password)),
            ]);

            // the IP is optimized for registration
            $key = 'login-attempts:'.$request->ip();

            if (RateLimiter::tooManyAttempts($key, 10)) {
                return response()->json([
                    'status' => false,
                    'message' => __('default.rate_limiter_message'),
                ], 200);
            }

            // session count
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

            // If the credentials are incorrect, it returns an error.
            return response()->json([
                'status' => true,
            ], 200); // Code 401: Unauthorized
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(), // Return validation errors
            ], 422); // HTTP 422 for validation errors
        } catch (\Throwable $th) {
            // \Log::info($th);

            return response()->json([
                'status' => false,
                'message' => __('default.error_message'), // Default error message
            ], 200); // Return general error response
        }
    }
}
