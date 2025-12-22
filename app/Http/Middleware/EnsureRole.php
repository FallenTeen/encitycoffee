<?php
namespace App\Http\Middleware;

use App\Models\Kalibrasi;
use App\Models\Shift;
use App\Models\StokEtalase;
use App\Models\Transaksi;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EnsureRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        if (! $user) {
            \Log::warning('EnsureRole: unauthenticated access attempt to ' . $request->path());
            abort(403);
        }

        if ($user->aktif === false || (is_numeric($user->aktif) && (int) $user->aktif === 0)) {
            abort(403, 'User tidak aktif');
        }

        $allowedRoles = [];
        foreach ($roles as $role) {
            $splitRoles = array_map('trim', explode(',', $role));
            $allowedRoles = array_merge($allowedRoles, $splitRoles);
        }

        $allowedRoles = array_unique($allowedRoles);
        $userRoleLower = strtolower((string) $user->role);

        if (! empty($allowedRoles)) {
            $allowedRolesLower = array_values(array_unique(array_map('strtolower', $allowedRoles)));
            if ($userRoleLower !== 'it_support' && ! in_array($userRoleLower, $allowedRolesLower, true)) {
                abort(403);
            }
        }

        $this->enforceHierarchyScope($request, $user);

        $response = $next($request);

        $this->auditIfNeeded($request, $user, $response);

        return $response;
    }

    private function enforceHierarchyScope(Request $request, $user): void
    {
        $role = strtolower((string) $user->role);
        if ($role === 'it_support') {
            return;
        }

        $allowedCabangIds = $this->allowedCabangIds($user);
        if (in_array($role, ['manager', 'supervisor', 'kasir'], true) && empty($allowedCabangIds)) {
            abort(403, 'Cabang belum ditetapkan');
        }

        $payloadRole = strtolower((string) $request->input('role', ''));
        if ($payloadRole !== '') {
            if ($role === 'kasir') {
                abort(403);
            }
            if ($role === 'supervisor' && $payloadRole !== 'kasir') {
                abort(403);
            }
            if ($role === 'manager' && ! in_array($payloadRole, ['supervisor', 'kasir'], true)) {
                abort(403);
            }
        }

        $cabangId = $request->integer('cabang_id');
        if ($cabangId > 0 && ! in_array($cabangId, $allowedCabangIds, true)) {
            abort(403, 'Tidak memiliki akses ke cabang ini');
        }

        $cabangIds = $request->input('cabang_ids');
        if (is_array($cabangIds)) {
            foreach ($cabangIds as $id) {
                $idInt = (int) $id;
                if ($idInt > 0 && ! in_array($idInt, $allowedCabangIds, true)) {
                    abort(403, 'Tidak memiliki akses ke cabang ini');
                }
            }
        }

        $routeCabang = $request->route('cabang');
        $routeCabangId = $this->routeId($routeCabang);
        if ($routeCabangId !== null && ! in_array($routeCabangId, $allowedCabangIds, true)) {
            abort(403, 'Tidak memiliki akses ke cabang ini');
        }

        $shiftParam = $request->route('shift');
        if ($shiftParam !== null) {
            $shift = $shiftParam instanceof Shift ? $shiftParam : Shift::find($this->routeId($shiftParam));
            if (! $shift) {
                abort(404);
            }
            if (! in_array((int) $shift->cabang_id, $allowedCabangIds, true)) {
                abort(403, 'Tidak memiliki akses ke cabang ini');
            }
            if ($role === 'kasir' && (int) $shift->user_id !== (int) $user->id) {
                abort(403, 'Tidak memiliki akses ke shift ini');
            }
        }

        $transaksiParam = $request->route('transaksi');
        if ($transaksiParam !== null) {
            $transaksi = $transaksiParam instanceof Transaksi ? $transaksiParam : Transaksi::find($this->routeId($transaksiParam));
            if (! $transaksi) {
                abort(404);
            }
            if (! in_array((int) $transaksi->cabang_id, $allowedCabangIds, true)) {
                abort(403, 'Tidak memiliki akses ke cabang ini');
            }
            if ($role === 'kasir' && (int) $transaksi->user_id !== (int) $user->id) {
                abort(403, 'Tidak memiliki akses ke transaksi ini');
            }
        }

        $stokEtalaseParam = $request->route('stokEtalase');
        if ($stokEtalaseParam !== null) {
            $stokEtalase = $stokEtalaseParam instanceof StokEtalase ? $stokEtalaseParam : StokEtalase::find($this->routeId($stokEtalaseParam));
            if (! $stokEtalase) {
                abort(404);
            }
            if (! in_array((int) $stokEtalase->cabang_id, $allowedCabangIds, true)) {
                abort(403, 'Tidak memiliki akses ke cabang ini');
            }
        }

        $kalibrasiParam = $request->route('kalibrasi');
        if ($kalibrasiParam !== null) {
            $kalibrasi = $kalibrasiParam instanceof Kalibrasi ? $kalibrasiParam : Kalibrasi::find($this->routeId($kalibrasiParam));
            if (! $kalibrasi) {
                abort(404);
            }
            $shift = Shift::find((int) $kalibrasi->shift_id);
            if ($shift) {
                if (! in_array((int) $shift->cabang_id, $allowedCabangIds, true)) {
                    abort(403, 'Tidak memiliki akses ke cabang ini');
                }
                if ($role === 'kasir' && (int) $shift->user_id !== (int) $user->id) {
                    abort(403, 'Tidak memiliki akses');
                }
            }
        }

        $targetUserParam = $request->route('user');
        if ($targetUserParam !== null) {
            $targetUser = $targetUserParam instanceof User ? $targetUserParam : User::find($this->routeId($targetUserParam));
            if (! $targetUser) {
                abort(404);
            }
            if ($role === 'kasir') {
                if ((int) $targetUser->id !== (int) $user->id) {
                    abort(403);
                }
                return;
            }

            $targetRole = strtolower((string) $targetUser->role);
            if ($targetRole === 'it_support') {
                abort(403);
            }
            if ($role === 'supervisor' && $targetRole !== 'kasir') {
                abort(403);
            }
            if ($role === 'manager' && ! in_array($targetRole, ['supervisor', 'kasir'], true)) {
                abort(403);
            }

            $targetCabangIds = $targetUser->cabang()->pluck('cabang.id')->all();
            $diff = array_values(array_diff($targetCabangIds, $allowedCabangIds));
            if (! empty($diff)) {
                abort(403, 'Tidak memiliki akses');
            }
        }
    }

    private function allowedCabangIds($user): array
    {
        if (! method_exists($user, 'cabang')) {
            return [];
        }
        return $user->cabang()->pluck('cabang.id')->all();
    }

    private function routeId($value): ?int
    {
        if ($value === null) {
            return null;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        if (is_object($value) && isset($value->id)) {
            return (int) $value->id;
        }
        return null;
    }

    private function auditIfNeeded(Request $request, $user, $response): void
    {
        $role = strtolower((string) $user->role);
        if ($role !== 'it_support') {
            return;
        }

        $method = strtoupper($request->getMethod());
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        if (is_object($response) && method_exists($response, 'getStatusCode')) {
            $status = (int) $response->getStatusCode();
            if ($status >= 400) {
                return;
            }
        }

        $subjectType = null;
        $subjectId = null;
        foreach (['user' => 'User', 'cabang' => 'Cabang', 'shift' => 'Shift', 'transaksi' => 'Transaksi'] as $key => $type) {
            $param = $request->route($key);
            $id = $this->routeId($param);
            if ($id !== null) {
                $subjectType = $type;
                $subjectId = $id;
                break;
            }
        }

        $payload = $this->redactPayload($request->all());

        try {
            DB::table('audit_logs')->insert([
                'actor_user_id' => (int) $user->id,
                'method' => $method,
                'path' => $request->path(),
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'payload' => empty($payload) ? null : json_encode($payload),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }
    }

    private function redactPayload($value)
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $key = is_string($k) ? strtolower($k) : '';
                if ($key !== '' && (str_contains($key, 'password') || str_contains($key, 'two_factor') || str_contains($key, 'token'))) {
                    $out[$k] = '[REDACTED]';
                    continue;
                }
                $out[$k] = $this->redactPayload($v);
            }
            return $out;
        }
        return $value;
    }
}
