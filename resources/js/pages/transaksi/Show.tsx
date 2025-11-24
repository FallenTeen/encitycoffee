import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function TransaksiShow({ transaksi }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: `Transaksi #${transaksi?.id ?? ''}`, href: `/transaksi/${transaksi?.id ?? ''}` }]}> 
            <Head title={`Transaksi #${transaksi?.id ?? ''}`} />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Transaksi #{transaksi?.id}</h1>
                <pre className="bg-muted p-4 rounded">{JSON.stringify(transaksi, null, 2)}</pre>
            </div>
        </AppLayout>
    );
}