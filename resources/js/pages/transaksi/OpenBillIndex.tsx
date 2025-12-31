import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';

interface OpenBillItem {
  id: number;
  nomor_open_bill?: string;
  total?: string | number;
  created_at?: string;
  status?: string;
  cabang?: { id: number; kode?: string; nama?: string } | null;
  user?: { id: number; name?: string } | null;
  shift?: { id: number; status?: string } | null;
}

interface Props {
  open_bills: {
    data: OpenBillItem[];
    total: number;
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
  };
  per_page: number;
}

function formatCurrency(value: string | number | undefined | null) {
  const n = typeof value === 'string' ? parseFloat(value) : value ?? 0;
  return n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
}

function formatDateTime(value?: string | null) {
  if (!value) return '-';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleString('id-ID');
}

export default function OpenBillIndex({ open_bills, per_page }: Props) {
  return (
    <AppLayout breadcrumbs={[{ title: 'Bill', href: '/transaksi/open-bill' }]}>
      <Head title="Bill" />
      <div className="space-y-6">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-xl font-semibold">Daftar Bill</h1>
            <div className="text-sm text-muted-foreground">Total: {open_bills?.total ?? 0}</div>
          </div>
        </div>

        <div className="rounded-md border">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left">
                  <th className="py-2 px-4">Nomor</th>
                  <th className="py-2 px-4">Cabang</th>
                  <th className="py-2 px-4">Kasir</th>
                  <th className="py-2 px-4">Total</th>
                  <th className="py-2 px-4">Status</th>
                  <th className="py-2 px-4">Waktu</th>
                  <th className="py-2 px-4">Aksi</th>
                </tr>
              </thead>
              <tbody>
                {(open_bills?.data ?? []).map((ob) => (
                  <tr key={ob.id} className="border-b last:border-0">
                    <td className="py-2 px-4 font-mono">{ob.nomor_open_bill ?? `OB-${ob.id}`}</td>
                    <td className="py-2 px-4">{ob.cabang?.nama ?? ob.cabang?.kode ?? '-'}</td>
                    <td className="py-2 px-4">{ob.user?.name ?? '-'}</td>
                    <td className="py-2 px-4">Rp {formatCurrency(ob.total)}</td>
                    <td className="py-2 px-4">
                      <span className="inline-flex items-center rounded bg-muted px-2 py-0.5 text-muted-foreground">
                        {ob.status ?? '-'}
                      </span>
                    </td>
                    <td className="py-2 px-4">{formatDateTime(ob.created_at)}</td>
                    <td className="py-2 px-4">
                      <Link href={`/transaksi/open-bill/${ob.id}`} className="text-primary underline">
                        Detail
                      </Link>
                    </td>
                  </tr>
                ))}
                {(open_bills?.data ?? []).length === 0 && (
                  <tr>
                    <td colSpan={7} className="px-4 py-8 text-center text-muted-foreground">
                      Belum ada open bill.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
          <div className="flex items-center justify-between border-t p-4 text-sm">
            <div>
              Halaman {open_bills?.current_page ?? 1} / {open_bills?.last_page ?? 1}
            </div>
            <div className="flex gap-2">
              <Button asChild variant="secondary" disabled={!open_bills?.prev_page_url}>
                <Link href={open_bills?.prev_page_url ?? '/transaksi/open-bill'}>Sebelumnya</Link>
              </Button>
              <Button asChild variant="secondary" disabled={!open_bills?.next_page_url}>
                <Link href={open_bills?.next_page_url ?? '/transaksi/open-bill'}>Berikutnya</Link>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
