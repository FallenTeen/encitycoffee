<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CabangAuthorization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $cabangId = $request->route('cabang_id') ?? $request->input('cabang_id');

        if (!$cabangId) {
            Log::warning('Cabang ID tidak ditemukan dalam request', [
                'user_id' => $user->id,
                'url' => $request->url(),
                'method' => $request->method()
            ]);
            return response()->json(['error' => 'Cabang ID diperlukan'], 400);
        }

        if ($user->isKasir() && !$user->cabang()->where('cabang.id', $cabangId)->exists()) {
            Log::warning('Unauthorized cabang access attempt', [
                'user_id' => $user->id,
                'cabang_id' => $cabangId,
                'user_cabang_ids' => $user->cabang()->pluck('cabang.id')->toArray()
            ]);
            return response()->json(['error' => 'Tidak memiliki akses ke cabang ini'], 403);
        }

        Log::info('Cabang authorization passed', [
            'user_id' => $user->id,
            'cabang_id' => $cabangId,
            'url' => $request->url()
        ]);

        return $next($request);
    }
}