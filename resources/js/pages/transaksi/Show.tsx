import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';

function formatCurrency(value: string | number | undefined | null) {
    const n = typeof value === 'string' ? parseFloat(value) : value ?? 0;
    return n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
}

export default function TransaksiShow({ transaksi, user_role }: any) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Transaksi', href: '/transaksi' },
                { title: transaksi?.nomor_invoice ?? `#${transaksi?.id ?? ''}`, href: `/transaksi/${transaksi?.id ?? ''}/show` },
            ]}
        >
            <Head title={`Transaksi ${transaksi?.nomor_invoice ?? `#${transaksi?.id ?? ''}`}`} />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">
                            Transaksi {transaksi?.nomor_invoice ?? `#${transaksi?.id ?? ''}`}
                        </h1>
                        <div className="text-sm text-muted-foreground">
                            Cabang: {transaksi?.cabang?.nama ?? transaksi?.cabang?.kode ?? '-'} · Kasir: {transaksi?.user?.name ?? '-'}
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <div className="rounded-md border bg-card p-4">
                        <div className="text-sm text-muted-foreground">Subtotal</div>
                        <div className="mt-1 text-lg font-semibold">Rp {formatCurrency(transaksi?.subtotal)}</div>
                    </div>
                    <div className="rounded-md border bg-card p-4">
                        <div className="text-sm text-muted-foreground">Diskon</div>
                        <div className="mt-1 text-lg font-semibold">Rp {formatCurrency(transaksi?.diskon)}</div>
                    </div>
                    <div className="rounded-md border bg-card p-4">
                        <div className="text-sm text-muted-foreground">Total</div>
                        <div className="mt-1 text-lg font-semibold">Rp {formatCurrency(transaksi?.total)}</div>
                    </div>
                </div>

                <div className="rounded-md border">
                    <div className="border-b px-4 py-2 text-sm font-medium">Item</div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left">
                                    <th className="py-2 px-4">Produk</th>
                                    <th className="py-2 px-4">Jumlah</th>
                                    <th className="py-2 px-4">Harga</th>
                                    <th className="py-2 px-4">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                {(transaksi?.item ?? []).map((item: any) => (
                                    <tr key={item.id} className="border-b last:border-0">
                                        <td className="py-2 px-4">
                                            <div className="font-medium">{item.produk?.nama ?? '-'}</div>
                                            <div className="text-xs text-muted-foreground">{item.produk?.sku ?? ''}</div>
                                        </td>
                                        <td className="py-2 px-4">{item.jumlah}</td>
                                        <td className="py-2 px-4">Rp {formatCurrency(item.harga_satuan)}</td>
                                        <td className="py-2 px-4">Rp {formatCurrency(item.subtotal)}</td>
                                    </tr>
                                ))}
                                {(transaksi?.item ?? []).length === 0 && (
                                    <tr>
                                        <td colSpan={4} className="px-4 py-8 text-center text-muted-foreground">
                                            Belum ada item.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="flex justify-between">
                    <div className="text-sm text-muted-foreground">
                        Status: <span className="font-medium">{transaksi?.status ?? '-'}</span>
                    </div>
                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <Link href="/transaksi">Kembali</Link>
                        </Button>
                        {/* Aksi sesuai role, misal manager/it_support bisa void/batal transaksi, kasir hanya lihat */}
                        {/* Contoh: */}
                        {/*
                        {['manager','it_support'].includes(user_role) && transaksi?.status !== 'batal' && (
                            <Button variant="destructive" onClick={() => {/* TODO: implement void action}>
                                Batalkan
                            </Button>
                        )}
                        */}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
