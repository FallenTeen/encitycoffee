<?php

namespace App\Http\Middleware;

use App\Models\OpenBill;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureDiscountPermission
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        if ($user->aktif === false || (is_numeric($user->aktif) && (int) $user->aktif === 0)) {
            abort(403, 'User tidak aktif');
        }

        $allowedRoles = [];
        foreach ($roles as $role) {
            $splitRoles = array_map('trim', explode(',', (string) $role));
            $allowedRoles = array_merge($allowedRoles, $splitRoles);
        }
        $allowedRoles = array_values(array_unique(array_map('strtolower', $allowedRoles)));

        $userRole = strtolower((string) $user->role);
        if (! empty($allowedRoles) && $userRole !== 'it_support' && ! in_array($userRole, $allowedRoles, true)) {
            abort(403);
        }

        $openBillParam = $request->route('openBill');
        if ($openBillParam !== null) {
            $openBill = $openBillParam instanceof OpenBill ? $openBillParam : OpenBill::find((int) $openBillParam);
            if (! $openBill) {
                abort(404);
            }

            if ($userRole !== 'it_support') {
                $allowedCabangIds = method_exists($user, 'cabang') ? $user->cabang()->pluck('cabang.id')->all() : [];
                if (empty($allowedCabangIds)) {
                    abort(403, 'Cabang belum ditetapkan');
                }
                if (! in_array((int) $openBill->cabang_id, $allowedCabangIds, true)) {
                    abort(403, 'Tidak memiliki akses ke cabang ini');
                }
                if ($userRole === 'kasir' && (int) $openBill->user_id !== (int) $user->id) {
                    abort(403, 'Tidak memiliki akses ke open bill ini');
                }
            }
        }

        return $next($request);
    }
}

