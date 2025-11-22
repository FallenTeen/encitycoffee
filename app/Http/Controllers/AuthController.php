<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $v = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'nama_perangkat' => 'required|string',
        ]);

        if ($v->fails()) {
            return response()->json(['error' => $v->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Kredensial salah'], 401);
        }

        if (! $user->aktif) {
            return response()->json(['error' => 'Akun tidak aktif'], 403);
        }

        // create token (using personal access tokens if available)
        if (method_exists($user, 'createToken')) {
            $token = $user->createToken($request->nama_perangkat)->plainTextToken;
        } else {
            // fallback: return a simple session-based auth
            Auth::login($user);
            $token = null;
        }

        $user->load('cabang');

        return response()->json(['user' => $user, 'token' => $token]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user && method_exists($user, 'currentAccessToken')) {
            $token = $user->currentAccessToken();
            if ($token) $token->delete();
        } else {
            Auth::logout();
        }

        return response()->json(['message' => 'Logout berhasil']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        if ($user) $user->load('cabang');
        return response()->json(['user' => $user]);
    }
}
