import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function StokCabang({ cabangId, stoks }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: `Stok Cabang #${cabangId}`, href: `/stok/cabang/${cabangId}` }]}> 
            <Head title={`Stok Cabang #${cabangId}`} />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Stok Cabang #{cabangId}</h1>
                <pre className="bg-muted p-4 rounded">{JSON.stringify(stoks, null, 2)}</pre>
            </div>
        </AppLayout>
    );
}