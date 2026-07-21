<?php

namespace App\Policies;

use App\Models\Cabang;
use App\Models\OpenBill;
use App\Models\Shift;
use App\Models\StokEtalase;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * BranchAccessPolicy - Centralized authorization for branch-scoped resources
 *
 * Aturan bisnis:
 * - IT Support: akses SEMUA cabang
 * - Manager: hanya cabang yang ditugaskan (via user_cabang + cabang_hierarchy)
 * - Supervisor: hanya cabang yang ditugaskan (via user_cabang)
 * - Kasir: HANYA 1 cabang yang ditugaskan (via user_cabang)
 *
 * PENTING: Selalu gunakan policy ini untuk check akses cabang.
 * Jangan pernah query langsung tanpa melewati policy.
 */
class BranchAccessPolicy
{
    use HandlesAuthorization;

    /**
     * Get list of cabang IDs yang bisa diakses user.
     * - IT Support: return null (artinya semua cabang)
     * - Manager/Supervisor/Kasir: return array of cabang IDs
     */
    public static function getAccessibleCabangIds(User $user): ?array
    {
        // IT Support bisa akses semua cabang
        if ($user->role === 'it_support') {
            return null; // null = unlimited
        }

        // Manager/Supervisor/Kasir: ambil dari user_cabang
        return $user->cabang()->pluck('cabang.id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Check if user has access to a specific cabang.
     * Returns true if user can access the cabang.
     */
    public static function canAccessCabang(User $user, int $cabangId): bool
    {
        $accessibleIds = self::getAccessibleCabangIds($user);

        // null = unlimited access
        if ($accessibleIds === null) {
            return true;
        }

        return in_array($cabangId, $accessibleIds, true);
    }

    /**
     * Filter query untuk membatasi ke cabang yang bisa diakses user.
     * Jika IT Support, return query as-is.
     * Jika user biasa, tambahkan whereIn('cabang_id', $accessibleIds).
     */
    public static function scopeToAccessibleCabang($query, User $user, string $cabangColumn = 'cabang_id')
    {
        $accessibleIds = self::getAccessibleCabangIds($user);

        if ($accessibleIds === null) {
            return $query; // IT Support - no restriction
        }

        return $query->whereIn($cabangColumn, $accessibleIds);
    }

    /**
     * Check akses ke Transaksi - otomatis dari relasi.
     */
    public function viewTransaksi(User $user, Transaksi $transaksi): bool
    {
        return self::canAccessCabang($user, (int) $transaksi->cabang_id);
    }

    /**
     * Check akses ke OpenBill.
     */
    public function viewOpenBill(User $user, OpenBill $openBill): bool
    {
        return self::canAccessCabang($user, (int) $openBill->cabang_id);
    }

    /**
     * Check akses ke Shift.
     */
    public function viewShift(User $user, Shift $shift): bool
    {
        return self::canAccessCabang($user, (int) $shift->cabang_id);
    }

    /**
     * Check akses untuk membuat Transaksi di Shift tertentu.
     * Memastikan:
     * 1. User bisa akses cabang shift
     * 2. Shift masih 'buka'
     */
    public function createTransaksiInShift(User $user, Shift $shift): bool
    {
        if ($shift->status !== 'buka') {
            return false;
        }
        return self::canAccessCabang($user, (int) $shift->cabang_id);
    }

    /**
     * Check akses untuk membuat Transaksi di cabang tertentu.
     * Biasanya user hanya boleh di cabangnya sendiri.
     */
    public function createTransaksiInCabang(User $user, int $cabangId): bool
    {
        return self::canAccessCabang($user, $cabangId);
    }

    /**
     * Check akses untuk membuat OpenBill di Shift tertentu.
     */
    public function createOpenBillInShift(User $user, Shift $shift): bool
    {
        return $this->createTransaksiInShift($user, $shift);
    }

    /**
     * Check akses ke StokEtalase.
     */
    public function viewStokEtalase(User $user, StokEtalase $stok): bool
    {
        return self::canAccessCabang($user, (int) $stok->cabang_id);
    }

    /**
     * Check akses ke Cabang.
     */
    public function viewCabang(User $user, Cabang $cabang): bool
    {
        return self::canAccessCabang($user, (int) $cabang->id);
    }
}
