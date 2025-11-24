import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function StokEdit({ stok }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Edit Stok', href: `/stok/${stok?.id ?? ''}/edit` }]}> 
            <Head title="Edit Stok" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Edit Stok #{stok?.id}</h1>
                <pre className="bg-muted p-4 rounded">{JSON.stringify(stok, null, 2)}</pre>
            </div>
        </AppLayout>
    );
}