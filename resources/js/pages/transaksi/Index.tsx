import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import transaksi from '@/routes/transaksi';
import { Head, Link, router, useForm } from '@inertiajs/react';

interface Props {
  transaksis: {
    data: Array<{
      id: number;
      nomor_invoice?: string;
      total?: string | number;
      status?: string;
      created_at?: string;
      cabang?: { id: number; kode?: string; nama?: string } | null;
      user?: { id: number; name?: string } | null;
      shift?: { id: number; status?: string } | null;
    }>;
    total: number;
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
  };
  filter_aktif: { status: string; per_page: number };
}

function formatCurrency(value: unknown) {
  const n = typeof value === 'number' ? value : parseFloat(String(value ?? 0));
  if (Number.isNaN(n)) return '0';
  return n.toLocaleString('id-ID');
}

function formatDateTime(value?: string) {
  if (!value) return '-';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleString('id-ID');
}

export default function TransaksiIndex({ transaksis, filter_aktif }: Props) {
  const { data, setData, get, processing, errors } = useForm({
    status: filter_aktif?.status ?? '',
    per_page: String(filter_aktif?.per_page ?? 15),
  });

  return (
    <AppLayout breadcrumbs={[{ title: 'Transaksi', href: transaksi.index().url }]}>
      <Head title="Transaksi" />
      <div className="space-y-6">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-xl font-semibold">Daftar Transaksi</h1>
            <div className="text-sm text-muted-foreground">Total: {transaksis?.total ?? 0}</div>
          </div>
        </div>

        <div className="rounded-md border p-4">
          <form
            className="grid grid-cols-1 gap-4 md:grid-cols-4"
            onSubmit={(e) => {
              e.preventDefault();
              get(transaksi.index().url, { preserveScroll: true, preserveState: true, replace: true });
            }}
          >
            <div className="space-y-1">
              <div className="text-sm font-medium">Status</div>
              <Select value={data.status} onValueChange={(v) => setData('status', v === '__all__' ? '' : v)}>
                <SelectTrigger>
                  <SelectValue placeholder="Semua status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="__all__">Semua status</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="selesai">Selesai</SelectItem>
                  <SelectItem value="batal">Batal</SelectItem>
                </SelectContent>
              </Select>
              <InputError message={errors.status} />
            </div>

            <div className="space-y-1">
              <div className="text-sm font-medium">Tampil</div>
              <Select value={data.per_page} onValueChange={(v) => setData('per_page', v)}>
                <SelectTrigger>
                  <SelectValue placeholder="Per halaman" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="15">15</SelectItem>
                  <SelectItem value="25">25</SelectItem>
                  <SelectItem value="50">50</SelectItem>
                  <SelectItem value="100">100</SelectItem>
                </SelectContent>
              </Select>
              <InputError message={errors.per_page as unknown as string} />
            </div>

            <div className="flex items-end gap-2 md:col-span-2">
              <Button type="submit" disabled={processing}>
                Terapkan
              </Button>
              <Button
                type="button"
                variant="secondary"
                onClick={() => {
                  setData({ status: '', per_page: '15' });
                  router.get(transaksi.index().url, {}, { preserveScroll: true, replace: true });
                }}
              >
                Reset
              </Button>
            </div>
          </form>
        </div>

        <div className="rounded-md border">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left">
                  <th className="py-2 px-4">Invoice</th>
                  <th className="py-2 px-4">Cabang</th>
                  <th className="py-2 px-4">Kasir</th>
                  <th className="py-2 px-4">Total</th>
                  <th className="py-2 px-4">Status</th>
                  <th className="py-2 px-4">Waktu</th>
                  <th className="py-2 px-4">Aksi</th>
                </tr>
              </thead>
              <tbody>
                {(transaksis?.data ?? []).map((t) => (
                  <tr key={t.id} className="border-b last:border-0">
                    <td className="py-2 px-4 font-mono">{t.nomor_invoice ?? `#${t.id}`}</td>
                    <td className="py-2 px-4">{t.cabang?.nama ?? t.cabang?.kode ?? '-'}</td>
                    <td className="py-2 px-4">{t.user?.name ?? '-'}</td>
                    <td className="py-2 px-4">Rp {formatCurrency(t.total)}</td>
                    <td className="py-2 px-4">
                      <span className="inline-flex items-center rounded bg-muted px-2 py-0.5 text-muted-foreground">
                        {t.status ?? '-'}
                      </span>
                    </td>
                    <td className="py-2 px-4">{formatDateTime(t.created_at)}</td>
                    <td className="py-2 px-4">
                      <Link href={transaksi.show(t.id)} className="text-primary underline">
                        Detail
                      </Link>
                    </td>
                  </tr>
                ))}
                {(transaksis?.data ?? []).length === 0 && (
                  <tr>
                    <td colSpan={7} className="px-4 py-8 text-center text-muted-foreground">
                      Belum ada transaksi.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
          <div className="flex items-center justify-between border-t p-4 text-sm">
            <div>
              Halaman {transaksis?.current_page ?? 1} / {transaksis?.last_page ?? 1}
            </div>
            <div className="flex gap-2">
              <Button asChild variant="secondary" disabled={!transaksis?.prev_page_url}>
                <Link href={transaksis?.prev_page_url ?? transaksi.index()}>Sebelumnya</Link>
              </Button>
              <Button asChild variant="secondary" disabled={!transaksis?.next_page_url}>
                <Link href={transaksis?.next_page_url ?? transaksi.index()}>Berikutnya</Link>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
