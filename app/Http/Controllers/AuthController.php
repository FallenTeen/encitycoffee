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
    public function showLogin()
    {
        if (Auth::check()) {
            $user = Auth::user();

            return match($user->role) {
                'it_support' => redirect()->route('admin.dashboard'),
                'manager' => redirect()->route('manager.dashboard'),
                'supervisor' => redirect()->route('supervisor.dashboard'),
                default => redirect()->route('dashboard'),
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

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            $error = ['email' => 'Kredensial salah'];
            Log::warning('Login failed', ['email' => (string) $request->email, 'expects_json' => (bool) $request->expectsJson()]);

            return $request->expectsJson()
                ? response()->json(['errors' => $error], 401)
                : back()->withErrors($error)->onlyInput('email');
        }

        // ❗ If API Login → No session, return token
        if ($request->expectsJson()) {
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
            'it_support' => redirect()->route('admin.dashboard'),
            'manager' => redirect()->route('manager.dashboard'),
            'supervisor' => redirect()->route('supervisor.dashboard'),
            default => redirect()->route('dashboard'),
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

        return redirect()->route('login')->with('success', 'Berhasil logout');
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
