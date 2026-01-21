import { cn } from '@/lib/utils';
import Swal from 'sweetalert2';

interface ToggleStatusBadgeProps {
    produk: {
        id: number;
        nama: string;
        aktif?: boolean | null;
    };
    canManage: boolean;
    onToggle: () => void;
    isToggling: boolean;
}

export default function ToggleStatusBadge({
    produk,
    canManage,
    onToggle,
    isToggling,
}: ToggleStatusBadgeProps) {
    const isActive = !!produk.aktif;
    const disabled = !canManage || isToggling;

    const handleClick = async () => {
        if (disabled) return;

        const result = await Swal.fire({
            title: isActive ? 'Nonaktifkan Produk' : 'Aktifkan Produk',
            text: isActive
                ? `Apakah Anda yakin ingin menonaktifkan "${produk.nama}"?`
                : `Apakah Anda yakin ingin mengaktifkan "${produk.nama}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: isActive ? 'Nonaktifkan' : 'Aktifkan',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            focusCancel: true,
        });

        if (!result.isConfirmed) {
            return;
        }

        onToggle();
    };

    return (
        <button
            type="button"
            role="switch"
            aria-checked={isActive}
            aria-disabled={disabled}
            title={
                disabled && !canManage
                    ? 'Anda tidak memiliki izin mengubah status'
                    : isActive
                      ? 'Klik untuk menonaktifkan'
                      : 'Klik untuk mengaktifkan'
            }
            onClick={handleClick}
            className={cn(
                'relative inline-flex h-5 w-9 items-center rounded-full transition-colors',
                isActive ? 'bg-green-500' : 'bg-gray-300',
                disabled
                    ? 'cursor-not-allowed opacity-60'
                    : 'cursor-pointer',
            )}
        >
            <span
                className={cn(
                    'inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform',
                    isActive ? 'translate-x-4' : 'translate-x-0.5',
                )}
            />
        </button>
    );
}
