import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function LaporanPenjualanProduk({ data }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Penjualan Produk', href: '/laporan/penjualan-produk' }]}> 
            <Head title="Penjualan Produk" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Penjualan Produk</h1>
                <pre className="bg-muted p-4 rounded">{JSON.stringify(data, null, 2)}</pre>
            </div>
        </AppLayout>
    );
}