import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function SupervisorShiftDetail({ shift, total_transaksi, total_pendapatan_tunai, total_pendapatan_qris }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Detail Shift', href: `/supervisor/shift/${shift?.id ?? ''}` }]}> 
            <Head title="Detail Shift" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Detail Shift #{shift?.id}</h1>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground">Total Transaksi</div>
                        <div className="text-2xl font-bold">{total_transaksi}</div>
                    </div>
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground">Pendapatan Tunai</div>
                        <div className="text-2xl font-bold">{total_pendapatan_tunai}</div>
                    </div>
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground">Pendapatan QRIS</div>
                        <div className="text-2xl font-bold">{total_pendapatan_qris}</div>
                    </div>
                </div>
                <pre className="bg-muted p-4 rounded">{JSON.stringify(shift, null, 2)}</pre>
            </div>
        </AppLayout>
    );
}