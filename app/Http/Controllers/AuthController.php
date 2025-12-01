<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
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

            return $request->expectsJson()
                ? response()->json(['errors' => $error], 401)
                : back()->withErrors($error)->onlyInput('email');
        }

        // ❗ If API Login → No session, return token
        if ($request->expectsJson()) {
            $user->tokens()->delete();
            $token = $user->createToken('pos-token')->plainTextToken;

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
            $request->user()->currentAccessToken()->delete();
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
        return response()->json([
            'user' => $request->user(),
        ]);
    }
}
