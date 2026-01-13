import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Head, router, useForm } from '@inertiajs/react';

interface ShiftRow {
    id: number;
    user?: any;
    cabang?: { id: number; nama: string; kode?: string | null };
    waktu_buka: string;
    waktu_tutup?: string | null;
    status: string;
    nama_kasir?: string | null;
    nama_kasir_list?: string[];
    total_transaksi: number;
    total_penjualan: number;
    akurasi_kas: number;
    durasi_shift_menit?: number | null;
}

interface StatistikRingkasan {
    total_shift: number;
    total_penjualan: number;
    rata_rata_per_shift: number;
    shift_dengan_selisih: number;
    total_selisih: number;
}

interface FilterAktif {
    tanggal_mulai?: string | null;
    tanggal_selesai?: string | null;
    cabang_id?: number | null;
    user_id?: number | null;
    status?: string | null;
}

interface Pagination<T> {
    data: T[];
    total: number;
}

interface Props {
    shift: Pagination<ShiftRow>;
    statistik_ringkasan: StatistikRingkasan;
    filter_aktif?: FilterAktif;
}

function formatRupiah(value: number | string | null | undefined) {
    const num = typeof value === 'string' ? Number(value) : value ?? 0;
    if (!Number.isFinite(num)) return '-';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(num);
}

export default function LaporanShift({ shift, statistik_ringkasan, filter_aktif }: Props) {
    const rows = shift?.data ?? [];

    const { data, setData, get, processing } = useForm({
        tanggal_mulai: filter_aktif?.tanggal_mulai ?? '',
        tanggal_selesai: filter_aktif?.tanggal_selesai ?? '',
        cabang_id: filter_aktif?.cabang_id ? String(filter_aktif.cabang_id) : '',
        user_id: filter_aktif?.user_id ? String(filter_aktif.user_id) : '',
        status: filter_aktif?.status ?? '',
    });

    const submit = () => {
        get('/laporan/shift', {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['shift', 'statistik_ringkasan', 'filter_aktif'],
        });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Laporan Shift', href: '/laporan/shift' }]}>
            <Head title="Laporan Shift" />
            <div className="space-y-6">
                <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Laporan Shift Kasir</h1>
                        <p className="text-sm text-muted-foreground">
                            Audit shift kasir berdasarkan periode, cabang, dan status shift.
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => {
                            router.get('/laporan/shift', {}, { preserveScroll: true, replace: true });
                            setData({
                                tanggal_mulai: '',
                                tanggal_selesai: '',
                                cabang_id: '',
                                user_id: '',
                                status: '',
                            } as any);
                        }}
                    >
                        Reset Filter
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filter Shift</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form
                            className="grid grid-cols-1 gap-4 md:grid-cols-4"
                            onSubmit={(e) => {
                                e.preventDefault();
                                submit();
                            }}
                        >
                            <div className="space-y-1">
                                <Label htmlFor="tanggal_mulai">Tanggal Mulai</Label>
                                <Input
                                    id="tanggal_mulai"
                                    type="date"
                                    value={data.tanggal_mulai}
                                    onChange={(e) => setData('tanggal_mulai', e.target.value)}
                                />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="tanggal_selesai">Tanggal Selesai</Label>
                                <Input
                                    id="tanggal_selesai"
                                    type="date"
                                    value={data.tanggal_selesai}
                                    onChange={(e) => setData('tanggal_selesai', e.target.value)}
                                />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="status">Status Shift</Label>
                                <Select
                                    value={data.status}
                                    onValueChange={(value) => setData('status', value)}
                                >
                                    <SelectTrigger id="status">
                                        <SelectValue placeholder="Semua status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">Semua status</SelectItem>
                                        <SelectItem value="buka">Buka</SelectItem>
                                        <SelectItem value="tutup">Tutup</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="user_id">ID Kasir (opsional)</Label>
                                <Input
                                    id="user_id"
                                    type="number"
                                    value={data.user_id}
                                    onChange={(e) => setData('user_id', e.target.value)}
                                    placeholder="Filter berdasarkan ID kasir"
                                />
                            </div>
                            <div className="md:col-span-4 flex items-end justify-end gap-2 pt-1">
                                <Button type="submit" disabled={processing}>
                                    Terapkan
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Total Shift</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">
                                {statistik_ringkasan?.total_shift ?? 0}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Total Penjualan</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">
                                {formatRupiah(statistik_ringkasan?.total_penjualan ?? 0)}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Rata-rata per Shift</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">
                                {formatRupiah(statistik_ringkasan?.rata_rata_per_shift ?? 0)}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Shift dengan Selisih Kas</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">
                                {statistik_ringkasan?.shift_dengan_selisih ?? 0}
                            </div>
                            <div className="text-xs text-muted-foreground">
                                Total selisih {formatRupiah(statistik_ringkasan?.total_selisih ?? 0)}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">
                            Daftar Shift dan Audit Kasir
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead>
                                    <tr className="border-b">
                                        <th className="text-left py-2 pr-4">Shift</th>
                                        <th className="text-left py-2 pr-4">Cabang</th>
                                        <th className="text-left py-2 pr-4">Kasir</th>
                                        <th className="text-right py-2 pr-4">Durasi (menit)</th>
                                        <th className="text-right py-2 pr-4">Total Transaksi</th>
                                        <th className="text-right py-2 pr-4">Total Penjualan</th>
                                        <th className="text-right py-2 pr-4">Selisih Kas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((row: ShiftRow) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="py-2 pr-4 align-top">
                                                <div className="font-medium">
                                                    #{row.id} ({row.status})
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {row.waktu_buka} - {row.waktu_tutup ?? '-'}
                                                </div>
                                            </td>
                                            <td className="py-2 pr-4 align-top">
                                                <div className="font-medium">
                                                    {row.cabang?.nama ?? '-'}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {row.cabang?.kode ?? ''}
                                                </div>
                                            </td>
                                            <td className="py-2 pr-4 align-top">
                                                <ul className="list-disc list-inside space-y-0.5">
                                                    {(row.nama_kasir_list ?? []).length === 0 &&
                                                        row.nama_kasir && <li>{row.nama_kasir}</li>}
                                                    {(row.nama_kasir_list ?? []).map((n, idx) => (
                                                        <li key={idx}>{n}</li>
                                                    ))}
                                                </ul>
                                            </td>
                                            <td className="py-2 pr-4 text-right align-top">
                                                {row.durasi_shift_menit ?? '-'}
                                            </td>
                                            <td className="py-2 pr-4 text-right align-top">
                                                {row.total_transaksi}
                                            </td>
                                            <td className="py-2 pr-4 text-right align-top">
                                                {formatRupiah(row.total_penjualan)}
                                            </td>
                                            <td className="py-2 pr-4 text-right align-top">
                                                {formatRupiah(row.akurasi_kas)}
                                            </td>
                                        </tr>
                                    ))}
                                    {rows.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={7}
                                                className="py-4 text-center text-sm text-muted-foreground"
                                            >
                                                Tidak ada data shift
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
