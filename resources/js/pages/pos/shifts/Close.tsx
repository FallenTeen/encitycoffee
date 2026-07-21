import { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type OpenBill = {
    id: number;
    nomor_open_bill: string;
    nama_pelanggan: string | null;
    total: number;
};

type OpenBillsResponse = {
    shift_id: number;
    has_open_bills: boolean;
    count: number;
    open_bills: OpenBill[];
};

type Props = {
    shift?: { id: number } | null;
};

type SnackbarState = {
    show: boolean;
    message: string;
    variant: 'warning' | 'error' | 'success';
};

export default function PosShiftsClose({ shift }: Props) {
    const shiftId = shift?.id;

    const [openBills, setOpenBills] = useState<OpenBill[]>([]);
    const [loadingBills, setLoadingBills] = useState(false);
    const [snackbar, setSnackbar] = useState<SnackbarState>({
        show: false,
        message: '',
        variant: 'warning',
    });
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [saldoAkhir, setSaldoAkhir] = useState('');
    const [catatan, setCatatan] = useState('');

    // Auto-dismiss snackbar
    useEffect(() => {
        if (snackbar.show) {
            const t = setTimeout(() => {
                setSnackbar((s) => ({ ...s, show: false }));
            }, 6000);
            return () => clearTimeout(t);
        }
    }, [snackbar.show]);

    const showSnackbar = (message: string, variant: SnackbarState['variant'] = 'warning') => {
        setSnackbar({ show: true, message, variant });
    };

    const fetchOpenBills = async (): Promise<OpenBill[]> => {
        if (!shiftId) return [];
        setLoadingBills(true);
        try {
            const csrfMeta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
            const headers: Record<string, string> = {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            };
            if (csrfMeta?.content) {
                headers['X-CSRF-TOKEN'] = csrfMeta.content;
            }

            const res = await fetch(`/api/pos/shift/${shiftId}/open-bills`, {
                method: 'GET',
                headers,
                credentials: 'include',
            });
            if (!res.ok) {
                throw new Error('Gagal memuat daftar bill terbuka');
            }
            const data: OpenBillsResponse = await res.json();
            setOpenBills(data.open_bills || []);
            return data.open_bills || [];
        } catch (err) {
            showSnackbar(
                err instanceof Error ? err.message : 'Gagal memuat daftar bill terbuka',
                'error',
            );
            return [];
        } finally {
            setLoadingBills(false);
        }
    };

    const handleTutupClick = async () => {
        if (!shiftId) return;

        // 1. Ambil daftar open bills
        const bills = await fetchOpenBills();

        // 2. Jika ada bill terbuka: tampilkan snackbar peringatan + dialog dengan daftar
        if (bills.length > 0) {
            showSnackbar(
                `Terdapat ${bills.length} bill yang belum diclose pada shift ini. ` +
                    'apakah anda yakin ingin close semua bill dan melakukan pengakhiran shift?',
                'warning',
            );
            setConfirmOpen(true);
            return;
        }

        // 3. Jika tidak ada bill terbuka: langsung dialog standar
        setConfirmOpen(true);
    };

    const performClose = async (forceCloseBills: boolean) => {
        if (!shiftId) return;
        setSubmitting(true);
        try {
            const csrfMeta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
            const headers: Record<string, string> = {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
            };
            if (csrfMeta?.content) {
                headers['X-CSRF-TOKEN'] = csrfMeta.content;
            }

            const res = await fetch(`/api/pos/shift/${shiftId}/tutup`, {
                method: 'POST',
                headers,
                credentials: 'include',
                body: JSON.stringify({
                    saldo_akhir: Number(saldoAkhir) || 0,
                    catatan: catatan || null,
                    force_close_open_bills: forceCloseBills,
                }),
            });

            if (res.status === 409) {
                // Server meminta konfirmasi (edge case: ada bill yang baru dibuat)
                const data = await res.json();
                if (data.open_bills) {
                    setOpenBills(data.open_bills);
                }
                showSnackbar(
                    data.confirmation_prompt ||
                        'apakah anda yakin ingin close semua bill dan melakukan pengakhiran shift?',
                    'warning',
                );
                return;
            }

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                throw new Error(data.error || `Gagal menutup shift (status ${res.status})`);
            }

            const data = await res.json();
            const closedCount = data.closed_open_bills_count || 0;
            const msg = closedCount > 0
                ? `Shift berhasil ditutup. ${closedCount} bill terbuka telah di-force close.`
                : 'Shift berhasil ditutup';
            showSnackbar(msg, 'success');

            // Tutup dialog lalu redirect ke home POS
            setConfirmOpen(false);
            setTimeout(() => {
                router.visit('/pos/home');
            }, 800);
        } catch (err) {
            showSnackbar(
                err instanceof Error ? err.message : 'Terjadi kesalahan saat menutup shift',
                'error',
            );
        } finally {
            setSubmitting(false);
        }
    };

    const handleConfirm = () => {
        const force = openBills.length > 0;
        performClose(force);
    };

    if (!shiftId) {
        return (
            <AppLayout>
                <Head title="POS - Tutup Shift" />
                <div className="space-y-6 p-4">
                    <h1 className="text-xl font-semibold">Tutup Shift</h1>
                    <p className="text-sm text-muted-foreground">ID shift tidak tersedia.</p>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title="POS - Tutup Shift" />

            <div className="space-y-6 p-4">
                <h1 className="text-xl font-semibold">Tutup Shift #{shiftId}</h1>

                <div className="space-y-3 rounded-lg border bg-card p-4 text-card-foreground shadow-sm">
                    <div>
                        <label className="mb-1 block text-sm font-medium">Saldo Akhir (tunai)</label>
                        <input
                            type="number"
                            min={0}
                            step="0.01"
                            value={saldoAkhir}
                            onChange={(e) => setSaldoAkhir(e.target.value)}
                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Masukkan saldo akhir"
                        />
                    </div>
                    <div>
                        <label className="mb-1 block text-sm font-medium">Catatan (opsional)</label>
                        <textarea
                            value={catatan}
                            onChange={(e) => setCatatan(e.target.value)}
                            maxLength={500}
                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            rows={2}
                            placeholder="Catatan tutup shift..."
                        />
                    </div>

                    <Button
                        onClick={handleTutupClick}
                        disabled={loadingBills || submitting}
                        className="w-full"
                    >
                        {loadingBills ? 'Memuat...' : submitting ? 'Menutup shift...' : 'Tutup Shift'}
                    </Button>
                </div>
            </div>

            {/* Snackbar warning untuk open bills */}
            {snackbar.show && (
                <div
                    className="fixed bottom-6 left-1/2 z-50 w-[90vw] max-w-md -translate-x-1/2 transform"
                    data-testid="snackbar"
                >
                    <div
                        className={
                            'rounded-lg px-4 py-3 text-sm shadow-lg ' +
                            (snackbar.variant === 'warning'
                                ? 'bg-amber-500 text-white'
                                : snackbar.variant === 'error'
                                  ? 'bg-red-600 text-white'
                                  : 'bg-emerald-600 text-white')
                        }
                    >
                        {snackbar.message}
                    </div>
                </div>
            )}

            {/* Dialog konfirmasi tutup shift (dengan/tanpa daftar bill) */}
            <Dialog open={confirmOpen} onOpenChange={setConfirmOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {openBills.length > 0
                                ? 'Tutup Shift dengan Bill Terbuka'
                                : 'Konfirmasi Tutup Shift'}
                        </DialogTitle>
                        <DialogDescription>
                            {openBills.length > 0
                                ? 'apakah anda yakin ingin close semua bill dan melakukan pengakhiran shift?'
                                : 'Apakah Anda yakin ingin mengakhiri shift ini?'}
                        </DialogDescription>
                    </DialogHeader>

                    {openBills.length > 0 && (
                        <div className="max-h-64 overflow-y-auto rounded-md border border-amber-300 bg-amber-50 p-3">
                            <p className="mb-2 text-sm font-semibold text-amber-800">
                                Daftar {openBills.length} bill yang belum diclose:
                            </p>
                            <ul className="space-y-1 text-sm">
                                {openBills.map((bill) => (
                                    <li
                                        key={bill.id}
                                        className="flex items-center justify-between rounded bg-white px-2 py-1"
                                    >
                                        <div>
                                            <span className="font-mono text-xs text-slate-600">
                                                {bill.nomor_open_bill}
                                            </span>
                                            {bill.nama_pelanggan && (
                                                <span className="ml-2 text-slate-800">
                                                    — {bill.nama_pelanggan}
                                                </span>
                                            )}
                                        </div>
                                        <span className="text-xs text-slate-500">
                                            Rp {Number(bill.total).toLocaleString('id-ID')}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                            <p className="mt-2 text-xs italic text-amber-700">
                                Semua bill di atas akan ditandai <strong>batal</strong> dan tidak
                                dapat dipulihkan.
                            </p>
                        </div>
                    )}

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setConfirmOpen(false)}
                            disabled={submitting}
                        >
                            Batal
                        </Button>
                        <Button
                            variant={openBills.length > 0 ? 'destructive' : 'default'}
                            onClick={handleConfirm}
                            disabled={submitting}
                            data-testid="confirm-close-shift"
                        >
                            {submitting
                                ? 'Memproses...'
                                : openBills.length > 0
                                  ? 'Ya, tutup shift & batalkan semua bill'
                                  : 'Ya, tutup shift'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
