<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route as RouteFacade;
use Inertia\Inertia;

class AuthController extends Controller
{
    private function backofficeUrl(string $path): string
    {
        $base = rtrim((string) config('app.backoffice_url'), '/');
        $path = ltrim($path, '/');

        return $base . '/' . $path;
    }

    public function showLogin()
    {
        if (Auth::check()) {
            $user = Auth::user();

            return match($user->role) {
                'it_support' => redirect()->away($this->backofficeUrl('admin/dashboard')),
                'manager' => redirect()->away($this->backofficeUrl('manager/dashboard')),
                'supervisor' => redirect()->away($this->backofficeUrl('supervisor/dashboard')),
                default => redirect()->away($this->backofficeUrl('dashboard')),
            };
        }

        return Inertia::render('auth/login', [
            'status' => session('status'),
            'canResetPassword' => true,
            'canRegister' => true,
        ]);
    }

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
        $expectsJson = (bool) $request->expectsJson();
        $clientIp = (string) $request->ip();

        // Use withTrashed() to find user even if soft-deleted, so we can give
        // a clear error message instead of generic "Kredensial salah"
        $user = User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            $error = ['email' => 'Kredensial salah'];

            // CRITICAL: Detailed logging for debugging production issues
            // Includes enough info to diagnose without leaking sensitive data
            Log::warning('Login failed', [
                'email' => $email,
                'expects_json' => $expectsJson,
                'ip' => $clientIp,
                'user_agent' => substr((string) $request->userAgent(), 0, 200),
                'user_found' => $user !== null,
                'user_id' => $user?->id,
                'user_trashed' => $user?->trashed() ?? false,
                'password_length' => strlen($password),
                'email_length' => strlen($email),
            ]);

            return $expectsJson
                ? response()->json(['errors' => $error], 401)
                : back()->withErrors($error)->onlyInput('email');
        }

        // Check if user is soft-deleted (this can happen if the user was deleted but
        // record still exists in DB due to SoftDeletes trait)
        if ($user->trashed()) {
            Log::warning('Login attempt for soft-deleted user', [
                'email' => $email,
                'user_id' => (int) $user->id,
                'deleted_at' => (string) $user->deleted_at,
                'ip' => $clientIp,
            ]);
            $error = ['email' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.'];
            return $expectsJson
                ? response()->json(['errors' => $error], 403)
                : back()->withErrors($error)->onlyInput('email');
        }

        // ❗ If API Login → No session, return token
        if ($expectsJson) {
            if ((bool) ($user->aktif ?? true) === false) {
                Log::warning('Mobile login rejected: user inactive', ['user_id' => (int) $user->id]);
                return response()->json(['error' => 'Akun tidak aktif'], 403);
            }
            if ((string) $user->role !== 'kasir') {
                Log::warning('Mobile login rejected: role not allowed', ['user_id' => (int) $user->id, 'role' => (string) $user->role]);
                return response()->json(['error' => 'Hanya kasir yang dapat login di aplikasi mobile'], 403);
            }

            $user->tokens()->delete();
            $token = $user->createToken('pos-token')->plainTextToken;

            // Load cabang relationship for mobile app - only active cabangs
            $user->load(['cabang' => function($query) {
                $query->where('aktif', true);
            }]);

            Log::info('Mobile login success', [
                'user_id' => (int) $user->id,
                'email' => $email,
                'cabang_count' => $user->cabang->count(),
            ]);

            return response()->json([
                'message' => 'Login berhasil',
                'user' => $user,
                'token' => $token,
            ]);
        }

        // 🔥 Web Login (session)
        Auth::login($user, $request->filled('remember'));
        $request->session()->regenerate();

        return match($user->role) {
            'it_support' => redirect()->away($this->backofficeUrl('admin/dashboard')),
            'manager' => redirect()->away($this->backofficeUrl('manager/dashboard')),
            'supervisor' => redirect()->away($this->backofficeUrl('supervisor/dashboard')),
            default => redirect()->away($this->backofficeUrl('dashboard')),
        };
    }

    public function logout(Request $request)
    {
        // ❗ If API logout → Delete Sanctum token
        if ($request->expectsJson()) {
            // Delete all tokens for this user to ensure complete logout
            $user = $request->user();
            Log::info('API logout called', ['user_id' => $user->id, 'token_count_before' => $user->tokens()->count()]);
            $user->tokens()->delete();
            Log::info('Tokens deleted after logout', ['user_id' => $user->id, 'token_count_after' => $user->tokens()->count()]);
            
            return response()->json(['message' => 'Logout berhasil']);
        }

        // 🔥 Web logout
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away($this->backofficeUrl('login'))->with('success', 'Berhasil logout');
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
