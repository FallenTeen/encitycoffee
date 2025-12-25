import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import transaksi from '@/routes/transaksi';
import { Button } from '@/components/ui/button';

interface OpenBillShowProps {
  open_bill: {
    id: number;
    nomor_open_bill?: string;
    subtotal?: string | number;
    diskon?: string | number;
    pajak?: string | number;
    total?: string | number;
    status?: string;
    catatan?: string | null;
    cabang?: { id: number; kode?: string; nama?: string } | null;
    user?: { id: number; name?: string } | null;
    shift?: { id: number; status?: string } | null;
    items?: Array<{
      id: number;
      jumlah: number;
      harga_satuan: string | number;
      subtotal: string | number;
      catatan?: string | null;
      produk?: { id: number; nama?: string; sku?: string } | null;
    }>;
  };
}

function formatCurrency(value: string | number | undefined | null) {
  const n = typeof value === 'string' ? parseFloat(value) : value ?? 0;
  return n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
}

export default function OpenBillShow({ open_bill }: OpenBillShowProps) {
  return (
    <AppLayout
      breadcrumbs={[
        { title: 'Bill', href: transaksi.openBill.index().url },
        { title: open_bill?.nomor_open_bill ?? `OB-${open_bill?.id ?? ''}`, href: transaksi.openBill.show(open_bill?.id ?? 0) },
      ]}
    >
      <Head title={`Bill ${open_bill?.nomor_open_bill ?? `#${open_bill?.id ?? ''}`}`} />
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-xl font-semibold">
              Bill {open_bill?.nomor_open_bill ?? `#${open_bill?.id ?? ''}`}
            </h1>
            <div className="text-sm text-muted-foreground">
              Cabang: {open_bill?.cabang?.nama ?? open_bill?.cabang?.kode ?? '-'} · Kasir:{' '}
              {open_bill?.user?.name ?? '-'}
            </div>
          </div>
        </div>

        <div className="grid gap-4 md:grid-cols-3">
          <div className="rounded-md border bg-card p-4">
            <div className="text-sm text-muted-foreground">Subtotal</div>
            <div className="mt-1 text-lg font-semibold">Rp {formatCurrency(open_bill?.subtotal)}</div>
          </div>
          <div className="rounded-md border bg-card p-4">
            <div className="text-sm text-muted-foreground">Diskon</div>
            <div className="mt-1 text-lg font-semibold">Rp {formatCurrency(open_bill?.diskon)}</div>
          </div>
          <div className="rounded-md border bg-card p-4">
            <div className="text-sm text-muted-foreground">Total</div>
            <div className="mt-1 text-lg font-semibold">Rp {formatCurrency(open_bill?.total)}</div>
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
                {(open_bill?.items ?? []).map((item) => (
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
                {(open_bill?.items ?? []).length === 0 && (
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
            Status: <span className="font-medium">{open_bill?.status ?? '-'}</span>
          </div>
          <div className="flex gap-2">
            <Button asChild variant="outline">
              <Link href={transaksi.openBill.index().url}>Kembali</Link>
            </Button>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
