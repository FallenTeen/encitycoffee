import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function StokCreate() {
    return (
        <AppLayout breadcrumbs={[{ title: 'Tambah Stok', href: '/stok/tambah' }]}> 
            <Head title="Tambah Stok" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Tambah Stok</h1>
                <p className="text-sm text-muted-foreground">Form penambahan stok akan disini.</p>
            </div>
        </AppLayout>
    );
}