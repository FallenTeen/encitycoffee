import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime } from '@/utils/formatters';

export default function LaporanTransaksi({
    transaksi,
    statistik,
    filter_aktif,
    cabang_options,
    kasir_options,
    shift_options,
}: any) {
    const rows = transaksi?.data ?? [];

    return (
        <AppLayout breadcrumbs={[{ title: 'Laporan Transaksi', href: '/laporan/transaksi' }]}>
            <Head title="Laporan Transaksi" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Laporan Riwayat Transaksi</h1>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground">Total Transaksi</div>
                        <div className="text-2xl font-bold">{statistik?.total_transaksi ?? 0}</div>
                    </div>
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground">Total Nilai</div>
                        <div className="text-2xl font-bold">{statistik?.total_nilai ?? 0}</div>
                    </div>
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground">Periode</div>
                        <div className="text-sm font-medium">
                            {filter_aktif?.tanggal_mulai} s/d {filter_aktif?.tanggal_selesai}
                        </div>
                    </div>
                </div>

                <div className="bg-muted p-4 rounded space-y-4">
                    <div className="flex items-center justify-between">
                        <div className="text-sm font-medium">Filter</div>
                        <a href="/laporan/transaksi" className="text-xs text-blue-600 hover:underline">
                            Reset
                        </a>
                    </div>
                    <form className="grid grid-cols-1 md:grid-cols-4 gap-4" method="get">
                        <div>
                            <div className="text-xs text-muted-foreground mb-1">Cabang</div>
                            <select
                                name="cabang_id"
                                defaultValue={filter_aktif?.cabang_id ?? ''}
                                className="w-full border rounded px-2 py-1 text-sm"
                            >
                                <option value="">Semua Cabang</option>
                                {(cabang_options ?? []).map((c: any) => (
                                    <option key={c.id} value={c.id}>
                                        {c.nama}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <div className="text-xs text-muted-foreground mb-1">Shift</div>
                            <select
                                name="shift_id"
                                defaultValue={filter_aktif?.shift_id ?? ''}
                                className="w-full border rounded px-2 py-1 text-sm"
                            >
                                <option value="">Semua Shift</option>
                                {(shift_options ?? []).map((s: any) => (
                                    <option key={s.id} value={s.id}>
                                        #{s.id} ({s.status}) {formatDateTime(s.waktu_buka)}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <div className="text-xs text-muted-foreground mb-1">Kasir</div>
                            <select
                                name="user_id"
                                defaultValue={filter_aktif?.user_id ?? ''}
                                className="w-full border rounded px-2 py-1 text-sm"
                            >
                                <option value="">Semua Kasir</option>
                                {(kasir_options ?? []).map((u: any) => (
                                    <option key={u.id} value={u.id}>
                                        {u.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <div className="text-xs text-muted-foreground mb-1">Status</div>
                            <select
                                name="status"
                                defaultValue={filter_aktif?.status ?? ''}
                                className="w-full border rounded px-2 py-1 text-sm"
                            >
                                <option value="">Semua Status</option>
                                <option value="selesai">Selesai</option>
                                <option value="pending">Pending</option>
                                <option value="batal">Batal</option>
                            </select>
                        </div>
                        <div className="flex flex-col gap-2">
                            <div className="grid grid-cols-2 gap-2">
                                <div>
                                    <div className="text-xs text-muted-foreground mb-1">Tanggal Mulai</div>
                                    <input
                                        type="date"
                                        name="tanggal_mulai"
                                        defaultValue={filter_aktif?.tanggal_mulai ?? ''}
                                        className="w-full border rounded px-2 py-1 text-sm"
                                    />
                                </div>
                                <div>
                                    <div className="text-xs text-muted-foreground mb-1">Tanggal Selesai</div>
                                    <input
                                        type="date"
                                        name="tanggal_selesai"
                                        defaultValue={filter_aktif?.tanggal_selesai ?? ''}
                                        className="w-full border rounded px-2 py-1 text-sm"
                                    />
                                </div>
                            </div>
                            <div className="flex justify-end">
                                <button
                                    type="submit"
                                    className="px-4 py-1.5 text-sm rounded bg-primary text-primary-foreground"
                                >
                                    Terapkan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div className="bg-muted p-4 rounded">
                    <div className="flex items-center justify-between mb-3">
                        <div className="text-sm text-muted-foreground">Daftar Transaksi</div>
                        <div className="text-xs text-muted-foreground">
                            Total data: {transaksi?.total ?? 0}
                        </div>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead>
                                <tr className="border-b">
                                    <th className="text-left py-2 pr-4">Waktu</th>
                                    <th className="text-left py-2 pr-4">Invoice</th>
                                    <th className="text-left py-2 pr-4">Cabang</th>
                                    <th className="text-left py-2 pr-4">Shift</th>
                                    <th className="text-left py-2 pr-4">Kasir</th>
                                    <th className="text-right py-2 pr-4">Total</th>
                                    <th className="text-left py-2 pr-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((row: any) => (
                                    <tr key={row.id} className="border-b last:border-0">
                                        <td className="py-2 pr-4 align-top">
                                            <div className="font-medium">
                                                {row.waktu_selesai ? formatDateTime(row.waktu_selesai) : '-'}
                                            </div>
                                        </td>
                                        <td className="py-2 pr-4 align-top">
                                            <div className="font-medium">{row.nomor_invoice}</div>
                                        </td>
                                        <td className="py-2 pr-4 align-top">
                                            <div className="font-medium">{row.cabang?.nama ?? '-'}</div>
                                        </td>
                                        <td className="py-2 pr-4 align-top">
                                            {row.shift ? (
                                                <div className="text-xs">
                                                    #{row.shift.id} ({row.shift.status})
                                                </div>
                                            ) : (
                                                '-'
                                            )}
                                        </td>
                                        <td className="py-2 pr-4 align-top">
                                            {row.kasir ? (
                                                <div className="text-xs">
                                                    {row.kasir.name}
                                                </div>
                                            ) : (
                                                '-'
                                            )}
                                        </td>
                                        <td className="py-2 pr-4 text-right align-top">
                                            {row.total}
                                        </td>
                                        <td className="py-2 pr-4 align-top">
                                            {row.status}
                                        </td>
                                    </tr>
                                ))}
                                {rows.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="py-4 text-center text-sm text-muted-foreground"
                                        >
                                            Tidak ada data transaksi
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
