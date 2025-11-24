import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function StokIndex({ stoks }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Stok', href: '/stok' }]}> 
            <Head title="Stok" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Daftar Stok</h1>
                <pre className="bg-muted p-4 rounded">{JSON.stringify(stoks, null, 2)}</pre>
            </div>
        </AppLayout>
    );
}