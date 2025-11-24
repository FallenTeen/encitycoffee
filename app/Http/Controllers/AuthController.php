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
            if ($user->role === 'it_support') {
                return redirect()->route('admin.dashboard');
            } elseif ($user->role === 'manager') {
                return redirect()->route('manager.dashboard');
            } elseif ($user->role === 'supervisor') {
                return redirect()->route('supervisor.dashboard');
            } else {
                return redirect()->route('dashboard');
            }
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

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            if ($request->expectsJson()) {
                return response()->json([
                    'user' => $user,
                    'token' => $user->createToken('pos-token')->plainTextToken,
                ]);
            }

            if ($user->role === 'it_support') {
                return redirect()->route('admin.dashboard');
            } elseif ($user->role === 'manager') {
                return redirect()->route('manager.dashboard');
            } elseif ($user->role === 'supervisor') {
                return redirect()->route('supervisor.dashboard');
            } else {
                return redirect()->route('dashboard');
            }
        }

        $errorResponse = ['email' => 'Kredensial salah'];

        if ($request->expectsJson()) {
            return response()->json(['errors' => $errorResponse], 422);
        }

        return back()->withErrors($errorResponse)->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Berhasil logout']);
        }

        return redirect()->route('login')->with('success', 'Berhasil logout');
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }
}
