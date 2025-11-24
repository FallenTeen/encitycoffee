import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function ShiftDetail({ shift, statistik, produk_terjual, kalibrasi_detail, timeline, perbandingan, jam_tersibuk, rekomendasi }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Detail Shift', href: `/laporan/shift/${shift?.id ?? ''}` }]}> 
            <Head title="Detail Shift" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Detail Shift #{shift?.id}</h1>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground">Total Transaksi</div>
                        <div className="text-2xl font-bold">{statistik?.total_transaksi}</div>
                    </div>
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground">Total Penjualan</div>
                        <div className="text-2xl font-bold">{statistik?.total_penjualan}</div>
                    </div>
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground">Selisih</div>
                        <div className="text-2xl font-bold">{statistik?.selisih}</div>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground mb-2">Perbandingan</div>
                        <pre className="text-sm">{JSON.stringify(perbandingan, null, 2)}</pre>
                    </div>
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground mb-2">Jam Tersibuk</div>
                        <pre className="text-sm">{JSON.stringify(jam_tersibuk, null, 2)}</pre>
                    </div>
                </div>

                <div className="bg-muted p-4 rounded">
                    <div className="text-sm text-muted-foreground mb-2">Rekomendasi</div>
                    <ul className="list-disc pl-6">
                        {(rekomendasi || []).map((r: string, i: number) => (
                            <li key={i}>{r}</li>
                        ))}
                    </ul>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground mb-2">Produk Terjual</div>
                        <pre className="text-sm">{JSON.stringify(produk_terjual, null, 2)}</pre>
                    </div>
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground mb-2">Kalibrasi</div>
                        <pre className="text-sm">{JSON.stringify(kalibrasi_detail, null, 2)}</pre>
                    </div>
                </div>

                <div className="bg-muted p-4 rounded">
                    <div className="text-sm text-muted-foreground mb-2">Timeline</div>
                    <pre className="text-sm">{JSON.stringify(timeline, null, 2)}</pre>
                </div>
            </div>
        </AppLayout>
    );
}