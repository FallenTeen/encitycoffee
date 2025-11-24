import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function ProdukCreate({ kategori, satuans }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Tambah Produk', href: '/produk/create' }]}> 
            <Head title="Tambah Produk" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Tambah Produk</h1>
                <p className="text-sm text-muted-foreground">Form tambah produk akan disini.</p>
                <pre className="bg-muted p-4 rounded">{JSON.stringify({ kategori, satuans }, null, 2)}</pre>
            </div>
        </AppLayout>
    );
}