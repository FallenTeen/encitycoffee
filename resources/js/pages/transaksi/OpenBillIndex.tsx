import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Head, Link, router } from '@inertiajs/react';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Trash2, AlertCircle, Receipt } from 'lucide-react';
import { useState } from 'react';

// ─── Types ────────────────────────────────────────────────────────────────────

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
  status?: string;
  auth?: { user?: { role?: string } };
}

// ─── Constants ─────────────────────────────────────────────────────────────────

type BillStatus = 'open' | 'closed' | 'batal';

const STATUS_TABS: { value: BillStatus; label: string }[] = [
  { value: 'open',   label: 'Open' },
  { value: 'closed', label: 'Closed' },
  { value: 'batal',  label: 'Dibatalkan' },
];

const STATUS_BADGE: Record<string, string> = {
  open:   'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
  closed: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  batal:  'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
};

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatCurrency(value: string | number | undefined | null) {
  const n = typeof value === 'string' ? parseFloat(value) : (value ?? 0);
  return (n as number).toLocaleString('id-ID', { maximumFractionDigits: 0 });
}

function formatDateTime(value?: string | null) {
  if (!value) return '-';
  const d = new Date(value);
  return Number.isNaN(d.getTime()) ? value : d.toLocaleString('id-ID');
}

// ─── Component ────────────────────────────────────────────────────────────────

export default function OpenBillIndex({ open_bills, per_page, status, auth }: Props) {
  const currentStatus = ((status || 'open').toLowerCase()) as BillStatus;
  const isItSupport   = auth?.user?.role === 'it_support';

  const [perPage,      setPerPage]      = useState(String(per_page ?? 15));
  const [isLoading,    setIsLoading]    = useState(false);

  // Soft delete state
  const [deleteOpen,   setDeleteOpen]   = useState(false);
  const [selectedBill, setSelectedBill] = useState<OpenBillItem | null>(null);
  const [deleteReason, setDeleteReason] = useState('');
  const [isDeleting,   setIsDeleting]   = useState(false);

  // ── Actions ─────────────────────────────────────────────────────────────────

  /** Ganti status tab → langsung navigasi */
  function handleStatusClick(s: BillStatus) {
    setIsLoading(true);
    router.get(
      '/transaksi/open-bill',
      { status: s, per_page: perPage },
      {
        preserveScroll: true,
        preserveState:  true,
        replace:        true,
        onFinish: () => setIsLoading(false),
      },
    );
  }

  /** Terapkan per_page */
  function handlePerPageChange(val: string) {
    setPerPage(val);
    setIsLoading(true);
    router.get(
      '/transaksi/open-bill',
      { status: currentStatus, per_page: val },
      {
        preserveScroll: true,
        preserveState:  true,
        replace:        true,
        onFinish: () => setIsLoading(false),
      },
    );
  }

  function handleSoftDelete() {
    if (!selectedBill || deleteReason.trim().length < 5) return;
    setIsDeleting(true);

    router.delete(`/transaksi/open-bill/${selectedBill.id}/soft-delete`, {
      data: { reason: deleteReason },
      preserveScroll: true,
      onSuccess: () => {
        setDeleteOpen(false);
        setSelectedBill(null);
        setDeleteReason('');
        router.reload({ only: ['open_bills'] });
      },
      onError: (errors) => {
        alert(Object.values(errors)[0] ?? 'Gagal menghapus bill');
      },
      onFinish: () => {
        setIsDeleting(false);
      },
    });
  }

  // ── Render ──────────────────────────────────────────────────────────────────

  return (
    <AppLayout breadcrumbs={[{ title: 'Bill', href: '/transaksi/open-bill' }]}>
      <Head title="Daftar Bill" />

      <div className="space-y-5">

        {/* Header */}
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-xl font-semibold">Daftar Bill</h1>
            <p className="text-sm text-muted-foreground">
              {open_bills?.total ?? 0} bill &bull;{' '}
              {STATUS_TABS.find((t) => t.value === currentStatus)?.label ?? currentStatus}
            </p>
          </div>
          {isItSupport && (
            <Link href="/admin/deleted-bills">
              <Button variant="outline" size="sm">
                <Trash2 className="mr-2 h-4 w-4" />
                Lihat Terhapus
              </Button>
            </Link>
          )}
        </div>

        {/* Filter card */}
        <div className="rounded-xl border bg-card p-5 shadow-sm space-y-4">

          {/* Status tabs (pill style, konsisten dengan halaman transaksi) */}
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
              Status
            </p>
            <div className="flex flex-wrap gap-2">
              {STATUS_TABS.map((tab) => (
                <button
                  key={tab.value}
                  type="button"
                  disabled={isLoading}
                  onClick={() => handleStatusClick(tab.value)}
                  className={[
                    'rounded-full border px-4 py-1.5 text-sm font-medium transition-all',
                    'disabled:cursor-not-allowed disabled:opacity-50',
                    currentStatus === tab.value
                      ? 'border-primary bg-primary text-primary-foreground shadow-sm'
                      : 'border-border bg-background hover:border-primary/60 hover:bg-muted',
                  ].join(' ')}
                >
                  {tab.label}
                </button>
              ))}
            </div>
          </div>

          {/* Per halaman */}
          <div className="flex flex-wrap items-end gap-3 border-t pt-4">
            <div className="w-28 space-y-1">
              <label className="text-xs font-medium text-muted-foreground">Per halaman</label>
              <Select value={perPage} onValueChange={handlePerPageChange} disabled={isLoading}>
                <SelectTrigger className="h-9"><SelectValue /></SelectTrigger>
                <SelectContent>
                  {['15','25','50','100'].map((v) => (
                    <SelectItem key={v} value={v}>{v}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {isLoading && (
              <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                <span className="h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent inline-block" />
                Memuat...
              </p>
            )}
          </div>
        </div>

        {/* Tabel */}
        <div className="rounded-xl border bg-card shadow-sm overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="border-b bg-muted/50">
                <tr>
                  {[
                    { label: 'Nomor',   cls: 'text-left' },
                    { label: 'Cabang',  cls: 'text-left' },
                    { label: 'Kasir',   cls: 'text-left' },
                    { label: 'Total',   cls: 'text-right' },
                    { label: 'Status',  cls: 'text-left' },
                    { label: 'Waktu',   cls: 'text-left' },
                    { label: 'Aksi',    cls: 'text-left' },
                  ].map((h) => (
                    <th
                      key={h.label}
                      className={`py-3 px-4 font-medium text-muted-foreground whitespace-nowrap ${h.cls}`}
                    >
                      {h.label}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y">
                {(open_bills?.data ?? []).map((ob) => (
                  <tr key={ob.id} className="hover:bg-muted/20 transition-colors">
                    <td className="py-3 px-4 font-mono text-xs text-muted-foreground">
                      {ob.nomor_open_bill ?? `OB-${ob.id}`}
                    </td>
                    <td className="py-3 px-4">{ob.cabang?.nama ?? ob.cabang?.kode ?? '-'}</td>
                    <td className="py-3 px-4">{ob.user?.name ?? '-'}</td>
                    <td className="py-3 px-4 text-right font-medium tabular-nums">
                      Rp {formatCurrency(ob.total)}
                    </td>
                    <td className="py-3 px-4">
                      <span className={[
                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                        STATUS_BADGE[ob.status ?? ''] ?? 'bg-muted text-muted-foreground',
                      ].join(' ')}>
                        {ob.status ?? '-'}
                      </span>
                    </td>
                    <td className="py-3 px-4 text-xs text-muted-foreground whitespace-nowrap">
                      {formatDateTime(ob.created_at)}
                    </td>
                    <td className="py-3 px-4">
                      <div className="flex items-center gap-2">
                        <Link
                          href={`/transaksi/open-bill/${ob.id}`}
                          className="text-xs text-primary underline underline-offset-2 hover:no-underline"
                        >
                          Detail
                        </Link>
                        {isItSupport && (
                          <>
                            <span className="select-none text-border">|</span>
                            <button
                              onClick={() => { setSelectedBill(ob); setDeleteOpen(true); }}
                              className="text-destructive transition-colors hover:text-destructive/70"
                              title="Hapus (soft delete)"
                            >
                              <Trash2 className="h-3.5 w-3.5" />
                            </button>
                          </>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}

                {(open_bills?.data ?? []).length === 0 && (
                  <tr>
                    <td colSpan={7} className="py-20 text-center">
                      <div className="flex flex-col items-center gap-2 text-muted-foreground">
                        <Receipt className="h-10 w-10 opacity-20" />
                        <p className="text-sm font-medium">Tidak ada bill</p>
                        <p className="text-xs opacity-60">
                          Status: {STATUS_TABS.find((t) => t.value === currentStatus)?.label}
                        </p>
                      </div>
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>

          {/* Pagination */}
          <div className="flex items-center justify-between border-t px-4 py-3 text-sm text-muted-foreground">
            <span>Halaman {open_bills?.current_page ?? 1} dari {open_bills?.last_page ?? 1}</span>
            <div className="flex gap-2">
              <Button asChild variant="outline" size="sm" disabled={!open_bills?.prev_page_url}>
                <Link href={open_bills?.prev_page_url ?? '#'}>← Sebelumnya</Link>
              </Button>
              <Button asChild variant="outline" size="sm" disabled={!open_bills?.next_page_url}>
                <Link href={open_bills?.next_page_url ?? '#'}>Berikutnya →</Link>
              </Button>
            </div>
          </div>
        </div>
      </div>

      {/* Soft Delete Dialog */}
      <Dialog
        open={deleteOpen}
        onOpenChange={(v) => { setDeleteOpen(v); if (!v) setDeleteReason(''); }}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Hapus Bill</DialogTitle>
            <DialogDescription asChild>
              <div className="mt-1 space-y-3 text-sm">
                <div className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 rounded-lg bg-muted p-3 text-xs">
                  <span className="text-muted-foreground">Nomor Bill</span>
                  <span className="font-mono font-medium">
                    {selectedBill?.nomor_open_bill ?? `OB-${selectedBill?.id}`}
                  </span>
                  <span className="text-muted-foreground">Total</span>
                  <span className="font-medium">Rp {formatCurrency(selectedBill?.total)}</span>
                  <span className="text-muted-foreground">Status</span>
                  <span className={[
                    'inline-flex w-fit items-center rounded-full px-2 py-0.5 text-xs font-medium',
                    STATUS_BADGE[selectedBill?.status ?? ''] ?? 'bg-muted text-muted-foreground',
                  ].join(' ')}>
                    {selectedBill?.status ?? '-'}
                  </span>
                </div>
                <Alert variant="destructive" className="py-2 text-xs">
                  <AlertCircle className="h-3.5 w-3.5" />
                  <AlertDescription>
                    Bill akan di-soft delete dan dapat dikembalikan lewat menu "Lihat Terhapus".
                  </AlertDescription>
                </Alert>
              </div>
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-2">
            <Label htmlFor="del-reason">
              Alasan Penghapusan <span className="text-destructive">*</span>
            </Label>
            <Textarea
              id="del-reason"
              rows={3}
              placeholder="Min. 5 karakter..."
              value={deleteReason}
              onChange={(e) => setDeleteReason(e.target.value)}
            />
          </div>

          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => { setDeleteOpen(false); setDeleteReason(''); }}
            >
              Batal
            </Button>
            <Button
              variant="destructive"
              onClick={handleSoftDelete}
              disabled={deleteReason.trim().length < 5 || isDeleting}
            >
              {isDeleting
                ? <><span className="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent inline-block" />Menghapus...</>
                : <><Trash2 className="mr-2 h-4 w-4" />Hapus</>
              }
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </AppLayout>
  );
}