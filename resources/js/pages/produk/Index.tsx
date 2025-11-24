import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function ProdukIndex({ produks }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Produk', href: '/produk' }]}> 
            <Head title="Produk" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Daftar Produk</h1>
                <pre className="bg-muted p-4 rounded">{JSON.stringify(produks, null, 2)}</pre>
            </div>
        </AppLayout>
    );
}