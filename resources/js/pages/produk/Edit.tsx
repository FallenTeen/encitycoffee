import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function ProdukEdit({ produk }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Edit Produk', href: `/produk/${produk?.id ?? ''}/edit` }]}> 
            <Head title="Edit Produk" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Edit Produk #{produk?.id}</h1>
                <pre className="bg-muted p-4 rounded">{JSON.stringify(produk, null, 2)}</pre>
            </div>
        </AppLayout>
    );
}