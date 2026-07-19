import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function ShiftDetail({
    shift,
    statistik,
    produk_terjual,
    kalibrasi_detail,
    timeline,
    perbandingan,
    jam_tersibuk,
    rekomendasi,
}: any) {
    const kasirList = shift?.nama_kasir_list ?? [];

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Detail Shift',
                    href: `/laporan/shift/${shift?.id ?? ''}`,
                },
            ]}
        >
            <Head title="Detail Shift" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">
                    Detail Shift #{shift?.id}
                </h1>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-4">
                    <div className="rounded bg-muted p-4">
                        <div className="text-sm text-muted-foreground">
                            Total Transaksi
                        </div>
                        <div className="text-2xl font-bold">
                            {statistik?.total_transaksi}
                        </div>
                    </div>
                    <div className="rounded bg-muted p-4">
                        <div className="text-sm text-muted-foreground">
                            Total Penjualan
                        </div>
                        <div className="text-2xl font-bold">
                            {new Intl.NumberFormat('id-ID', {
                                style: 'currency',
                                currency: 'IDR',
                                maximumFractionDigits: 0,
                            }).format(statistik?.total_penjualan ?? 0)}
                        </div>
                    </div>
                    <div className="rounded bg-muted p-4">
                        <div className="text-sm text-muted-foreground">
                            Total Diskon
                        </div>
                        <div className="text-2xl font-bold text-orange-600">
                            {new Intl.NumberFormat('id-ID', {
                                style: 'currency',
                                currency: 'IDR',
                                maximumFractionDigits: 0,
                            }).format(statistik?.total_diskon ?? 0)}
                        </div>
                    </div>
                    <div className="rounded bg-muted p-4">
                        <div className="text-sm text-muted-foreground">
                            Selisih Kas
                        </div>
                        <div className="text-2xl font-bold">
                            {new Intl.NumberFormat('id-ID', {
                                style: 'currency',
                                currency: 'IDR',
                                maximumFractionDigits: 0,
                            }).format(statistik?.selisih ?? 0)}
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div className="rounded bg-muted p-4">
                        <div className="mb-1 text-sm text-muted-foreground">
                            Cabang
                        </div>
                        <div className="font-medium">
                            {shift?.cabang?.nama ?? '-'}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            {shift?.cabang?.kode ?? ''}
                        </div>
                        <div className="mt-3 mb-1 text-sm text-muted-foreground">
                            Kasir
                        </div>
                        <ul className="list-inside list-disc space-y-0.5">
                            {kasirList.length === 0 && shift?.nama_kasir && (
                                <li>{shift.nama_kasir}</li>
                            )}
                            {kasirList.map((n: string, i: number) => (
                                <li key={i}>{n}</li>
                            ))}
                        </ul>
                    </div>
                    <div className="rounded bg-muted p-4">
                        <div className="mb-2 text-sm text-muted-foreground">
                            Perbandingan
                        </div>
                        <pre className="text-sm">
                            {JSON.stringify(perbandingan, null, 2)}
                        </pre>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div className="rounded bg-muted p-4">
                        <div className="mb-2 text-sm text-muted-foreground">
                            Jam Tersibuk
                        </div>
                        <pre className="text-sm">
                            {JSON.stringify(jam_tersibuk, null, 2)}
                        </pre>
                    </div>
                    <div className="rounded bg-muted p-4">
                        <div className="mb-2 text-sm text-muted-foreground">
                            Rekomendasi
                        </div>
                        <ul className="list-disc pl-6">
                            {(rekomendasi || []).map((r: string, i: number) => (
                                <li key={i}>{r}</li>
                            ))}
                        </ul>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div className="rounded bg-muted p-4">
                        <div className="mb-2 text-sm text-muted-foreground">
                            Produk Terjual
                        </div>
                        <pre className="text-sm">
                            {JSON.stringify(produk_terjual, null, 2)}
                        </pre>
                    </div>
                    <div className="rounded bg-muted p-4">
                        <div className="mb-2 text-sm text-muted-foreground">
                            Kalibrasi
                        </div>
                        <pre className="text-sm">
                            {JSON.stringify(kalibrasi_detail, null, 2)}
                        </pre>
                    </div>
                </div>

                <div className="rounded bg-muted p-4">
                    <div className="mb-2 text-sm text-muted-foreground">
                        Timeline
                    </div>
                    <pre className="text-sm">
                        {JSON.stringify(timeline, null, 2)}
                    </pre>
                </div>
            </div>
        </AppLayout>
    );
}
