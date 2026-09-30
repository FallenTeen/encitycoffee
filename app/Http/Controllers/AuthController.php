<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ApiErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mobile POS session endpoints.
 *
 * Web/backoffice login is owned by Fortify, so every response here is JSON.
 */
class AuthController extends Controller
{
    /**
     * Mobile POS login.
     *
     * Response contract:
     *   200 => authenticated, a bearer token is issued
     *   401 => the supplied credential is not valid
     *   403 => the account exists but is not allowed to log in
     *   422 => the payload failed validation
     *   500 => unexpected failure (generic message, detail goes to the log)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Normalize email: trim whitespace and lowercase to avoid case-sensitivity issues
        // that may occur across different DB collations or user typing errors.
        $email = strtolower(trim((string) $request->email));
        $password = (string) $request->password;
        $clientIp = (string) $request->ip();

        try {
            // Use withTrashed() to find user even if soft-deleted, so we can give
            // a clear error message instead of generic "Kredensial salah"
            $user = User::withTrashed()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();
        } catch (Throwable $e) {
            // A lookup failure is a server problem, never a credential problem.
            // Surfacing it as 401 would make the mobile client discard the session.
            return $this->loginLookupFailure($email, $clientIp, $e);
        }

        if (! $user || ! Hash::check($password, $user->password)) {
            $error = ['email' => 'Kredensial salah'];

            // CRITICAL: Detailed logging for debugging production issues
            // Includes enough info to diagnose without leaking sensitive data
            Log::warning('Login failed', [
                'email' => $email,
                'ip' => $clientIp,
                'user_agent' => substr((string) $request->userAgent(), 0, 200),
                'user_found' => $user !== null,
                'user_id' => $user?->id,
                'user_trashed' => $user?->trashed() ?? false,
                'password_length' => strlen($password),
                'email_length' => strlen($email),
            ]);

            return ApiErrorResponse::make(401, 'Email atau password salah.', $error);
        }

        // Check if user is soft-deleted (this can happen if the user was deleted but
        // record still exists in DB due to SoftDeletes trait).
        // An existing but disabled account is a permission problem (403), never a
        // credential problem (401).
        if ($user->trashed()) {
            Log::warning('Login attempt for soft-deleted user', [
                'email' => $email,
                'user_id' => (int) $user->id,
                'deleted_at' => (string) $user->deleted_at,
                'ip' => $clientIp,
            ]);

            $message = 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.';

            return ApiErrorResponse::forbidden($message);
        }

        if ((bool) ($user->aktif ?? true) === false) {
            Log::warning('Mobile login rejected: user inactive', ['user_id' => (int) $user->id]);

            return ApiErrorResponse::forbidden('Akun tidak aktif');
        }

        if ((string) $user->role !== 'kasir') {
            Log::warning('Mobile login rejected: role not allowed', ['user_id' => (int) $user->id, 'role' => (string) $user->role]);

            return ApiErrorResponse::forbidden('Hanya kasir yang dapat login di aplikasi mobile');
        }

        try {
            // Session policy is unchanged: one active device per cashier
            // (every previous token is revoked, a new one is issued).
            // Wrapped in a transaction so a mid-flight failure cannot leave
            // the cashier with no usable token at all.
            $token = DB::transaction(function () use ($user) {
                $user->tokens()->delete();

                return $user->createToken('pos-token')->plainTextToken;
            });

            // Load cabang relationship for mobile app - only active cabangs
            $user->load(['cabang' => function ($query) {
                $query->where('aktif', true);
            }]);
        } catch (Throwable $e) {
            // Token issuance is a server concern. Report it as 500 so the
            // mobile client does not treat it as an authentication failure.
            Log::error('Mobile login token issuance failed', [
                'user_id' => (int) $user->id,
                'email' => $email,
                'ip' => $clientIp,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return ApiErrorResponse::make(500, ApiErrorResponse::GENERIC_SERVER_ERROR);
        }

        Log::info('Mobile login success', [
            'user_id' => (int) $user->id,
            'email' => $email,
            'cabang_count' => $user->cabang->count(),
        ]);

        return response()->json([
            'message' => 'Login berhasil',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    /**
     * Guard for failures while looking the user up.
     *
     * Returns 500 (not 401) so the mobile client keeps the current session and
     * only surfaces a retryable error.
     */
    private function loginLookupFailure(string $email, string $clientIp, Throwable $e)
    {
        Log::error('Login user lookup failed', [
            'email' => $email,
            'ip' => $clientIp,
            'exception' => $e::class,
            'message' => $e->getMessage(),
        ]);

        return ApiErrorResponse::make(500, ApiErrorResponse::GENERIC_SERVER_ERROR);
    }

    public function logout(Request $request)
    {
        // Only routed under /api/pos/auth/logout, so this is always the API path.
        // Delete all tokens for this user to ensure complete logout
        $user = $request->user();
        Log::info('API logout called', ['user_id' => $user->id, 'token_count_before' => $user->tokens()->count()]);

        try {
            $user->tokens()->delete();
        } catch (Throwable $e) {
            // A failed revoke must not be reported as a credential problem.
            Log::error('API logout token revoke failed', [
                'user_id' => (int) $user->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return ApiErrorResponse::make(500, ApiErrorResponse::GENERIC_SERVER_ERROR);
        }

        Log::info('Tokens deleted after logout', ['user_id' => $user->id, 'token_count_after' => $user->tokens()->count()]);

        return response()->json(['message' => 'Logout berhasil']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        Log::info('API me endpoint called', ['user_id' => $user?->id, 'has_user' => $user !== null]);

        return response()->json([
            'user' => $user,
        ]);
    }
}
