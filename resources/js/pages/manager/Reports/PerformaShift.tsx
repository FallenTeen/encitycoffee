import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function ManagerPerformaShift({ performance }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Performa Shift', href: '/manager/performa-shift' }]}> 
            <Head title="Performa Shift" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Performa Shift</h1>
                <p className="text-sm text-muted-foreground">Performa per shift berdasarkan transaksi dan pendapatan.</p>
                <pre className="bg-muted p-4 rounded">{JSON.stringify({ performance }, null, 2)}</pre>
            </div>
        </AppLayout>
    );
}