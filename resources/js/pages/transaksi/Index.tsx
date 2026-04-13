import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Trash2, AlertCircle, CalendarRange, Zap } from 'lucide-react';
import { useMemo, useState } from 'react';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Props {
  transaksis: {
    data: Array<{
      id: number;
      nomor_invoice?: string;
      nama_pelanggan?: string | null;
      subtotal?: string | number;
      diskon?: string | number | null;
      diskon_persen?: string | number | null;
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
  filter_aktif: {
    status: string;
    diskon_status?: string;
    min_diskon?: number | null;
    max_diskon?: number | null;
    sort_by?: string;
    sort_dir?: string;
    search?: string;
    per_page: number;
    tanggal_mulai?: string;
    tanggal_selesai?: string;
  };
  auth?: { user?: { role?: string } };
}

type QuickRange = 'hari_ini' | 'kemarin' | '7_hari' | '30_hari' | 'bulan_ini' | 'bulan_lalu' | 'kustom';

// ─── Constants ─────────────────────────────────────────────────────────────────

const QUICK_RANGES: { value: QuickRange; label: string }[] = [
  { value: 'hari_ini',   label: 'Hari Ini' },
  { value: 'kemarin',    label: 'Kemarin' },
  { value: '7_hari',     label: '7 Hari Terakhir' },
  { value: '30_hari',    label: '30 Hari Terakhir' },
  { value: 'bulan_ini',  label: 'Bulan Ini' },
  { value: 'bulan_lalu', label: 'Bulan Lalu' },
  { value: 'kustom',     label: 'Custom Range' },
];

const STATUS_BADGE: Record<string, string> = {
  selesai: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
  batal:   'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
};

// ─── Helpers ──────────────────────────────────────────────────────────────────

function todayISO() {
  return new Date().toISOString().slice(0, 10);
}

/**
 * Hitung tanggal mulai/selesai untuk setiap preset.
 * Dipanggil langsung saat klik pill — tidak bergantung pada state.
 */
function datesForPreset(preset: Exclude<QuickRange, 'kustom'>): { mulai: string; selesai: string } {
  const now   = new Date();
  const today = now.toISOString().slice(0, 10);

  const isoOffset = (days: number) => {
    const d = new Date(now);
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
  };

  switch (preset) {
    case 'hari_ini':   return { mulai: today,         selesai: today };
    case 'kemarin':    return { mulai: isoOffset(-1),  selesai: isoOffset(-1) };
    case '7_hari':     return { mulai: isoOffset(-6),  selesai: today };
    case '30_hari':    return { mulai: isoOffset(-29), selesai: today };
    case 'bulan_ini': {
      const first = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10);
      return { mulai: first, selesai: today };
    }
    case 'bulan_lalu': {
      const first = new Date(now.getFullYear(), now.getMonth() - 1, 1).toISOString().slice(0, 10);
      const last  = new Date(now.getFullYear(), now.getMonth(), 0).toISOString().slice(0, 10);
      return { mulai: first, selesai: last };
    }
  }
}

/** Cocokkan tanggal dari server ke salah satu preset (untuk highlight pill aktif) */
function detectPreset(mulai?: string, selesai?: string): QuickRange {
  if (!mulai || !selesai) return 'hari_ini';
  const presets: Exclude<QuickRange, 'kustom'>[] = [
    'hari_ini', 'kemarin', '7_hari', '30_hari', 'bulan_ini', 'bulan_lalu',
  ];
  for (const p of presets) {
    const d = datesForPreset(p);
    if (d.mulai === mulai && d.selesai === selesai) return p;
  }
  return 'kustom';
}

function formatCurrency(value: unknown) {
  const n = typeof value === 'number' ? value : parseFloat(String(value ?? 0));
  return Number.isNaN(n) ? '0' : n.toLocaleString('id-ID');
}

function formatDateTime(value?: string) {
  if (!value) return '-';
  const d = new Date(value);
  return Number.isNaN(d.getTime()) ? value : d.toLocaleString('id-ID');
}

// ─── Component ────────────────────────────────────────────────────────────────

export default function TransaksiIndex({ transaksis, filter_aktif, auth }: Props) {
  const today = useMemo(todayISO, []);

  // Inisialisasi state dari props server
  const serverMulai    = filter_aktif?.tanggal_mulai   ?? today;
  const serverSelesai  = filter_aktif?.tanggal_selesai ?? today;
  const detectedPreset = detectPreset(serverMulai, serverSelesai);

  const [quickRange,    setQuickRange]    = useState<QuickRange>(detectedPreset);
  const [customMulai,   setCustomMulai]   = useState(serverMulai);
  const [customSelesai, setCustomSelesai] = useState(serverSelesai);
  const [status,        setStatus]        = useState(filter_aktif?.status ?? '');
  const [diskonStatus,  setDiskonStatus]  = useState(filter_aktif?.diskon_status ?? '');
  const [search,        setSearch]        = useState(filter_aktif?.search ?? '');
  const [sortBy,        setSortBy]        = useState(filter_aktif?.sort_by ?? 'tanggal');
  const [sortDir,       setSortDir]       = useState(filter_aktif?.sort_dir ?? 'desc');
  const [minDiskon,     setMinDiskon]     = useState(filter_aktif?.min_diskon ?? null);
  const [maxDiskon,     setMaxDiskon]     = useState(filter_aktif?.max_diskon ?? null);
  const [perPage,       setPerPage]       = useState(String(filter_aktif?.per_page ?? 15));
  const [isLoading,     setIsLoading]     = useState(false);
  const [dateError,     setDateError]     = useState<string | null>(null);

  // Soft delete state
  const [deleteOpen,   setDeleteOpen]   = useState(false);
  const [deleteTx,     setDeleteTx]     = useState<any>(null);
  const [deleteReason, setDeleteReason] = useState('');
  const [isDeleting,   setIsDeleting]   = useState(false);

  const isItSupport = auth?.user?.role === 'it_support';

  // Tanggal aktual yang akan dikirim ke server
  const resolvedDates = useMemo(() => {
    if (quickRange === 'kustom') return { mulai: customMulai, selesai: customSelesai };
    return datesForPreset(quickRange);
  }, [quickRange, customMulai, customSelesai]);

  // Label periode untuk subtitle header
  const periodLabel = useMemo(() => {
    if (quickRange !== 'kustom') {
      return QUICK_RANGES.find((r) => r.value === quickRange)?.label ?? '';
    }
    return resolvedDates.mulai === resolvedDates.selesai
      ? resolvedDates.mulai
      : `${resolvedDates.mulai} – ${resolvedDates.selesai}`;
  }, [quickRange, resolvedDates]);

  // ── Actions ─────────────────────────────────────────────────────────────────

  function validateCustomDates(): string | null {
    if (quickRange !== 'kustom') return null;
    const { mulai, selesai } = resolvedDates;
    if (!mulai || !selesai) return 'Tanggal mulai dan selesai wajib diisi';
    if (selesai < mulai)    return 'Tanggal akhir tidak boleh lebih awal dari tanggal mulai';
    if (mulai > today || selesai > today) return 'Tanggal tidak boleh di masa depan';
    return null;
  }

  /**
   * Kirim request filter ke server.
   * Menerima `dates` override agar bisa langsung dipanggil dari handlePresetClick
   * tanpa menunggu state React update.
   */
  function doSubmit(dates: { mulai: string; selesai: string }, currentStatus: string, currentPerPage: string) {
    setIsLoading(true);
    router.get(
      '/transaksi',
      {
        ...(currentStatus ? { status: currentStatus } : {}),
        ...(diskonStatus ? { diskon_status: diskonStatus } : {}),
        ...(search.trim() ? { search: search.trim() } : {}),
        ...(sortBy ? { sort_by: sortBy } : {}),
        ...(sortDir ? { sort_dir: sortDir } : {}),
        ...(minDiskon !== null && minDiskon !== undefined ? { min_diskon: minDiskon } : {}),
        ...(maxDiskon !== null && maxDiskon !== undefined ? { max_diskon: maxDiskon } : {}),
        per_page:        currentPerPage,
        tanggal_mulai:   dates.mulai,
        tanggal_selesai: dates.selesai,
      },
      {
        preserveScroll: true,
        preserveState:  true,
        replace:        true,
        onFinish: () => setIsLoading(false),
      },
    );
  }

  /** Klik preset pill → update state DAN langsung submit (tidak perlu klik Terapkan) */
  function handlePresetClick(preset: QuickRange) {
    setQuickRange(preset);
    setDateError(null);

    if (preset !== 'kustom') {
      // Hitung tanggal secara langsung — jangan ambil dari state (belum terupdate)
      const dates = datesForPreset(preset);
      doSubmit(dates, status, perPage);
    }
    // Kalau 'kustom': user set tanggal dulu, lalu klik Terapkan
  }

  /** Tombol Terapkan — dipakai untuk custom range dan juga ubah status/perPage */
  function handleSubmit() {
    const err = validateCustomDates();
    if (err) { setDateError(err); return; }
    setDateError(null);
    doSubmit(resolvedDates, status, perPage);
  }

  function handleReset() {
    const dates = datesForPreset('hari_ini');
    setQuickRange('hari_ini');
    setCustomMulai(today);
    setCustomSelesai(today);
    setStatus('');
    setDiskonStatus('');
    setSearch('');
    setSortBy('tanggal');
    setSortDir('desc');
    setMinDiskon(null);
    setMaxDiskon(null);
    setPerPage('15');
    setDateError(null);
    doSubmit(dates, '', '15');
  }

  function buildExportParams() {
    const err = validateCustomDates();
    if (err) { setDateError(err); return null; }
    const params = new URLSearchParams({
      status:          status || '',
      diskon_status:   diskonStatus || '',
      sort_by:         sortBy || '',
      sort_dir:        sortDir || '',
      search:          search.trim() || '',
      tanggal_mulai:   resolvedDates.mulai,
      tanggal_selesai: resolvedDates.selesai,
    });
    if (minDiskon !== null && minDiskon !== undefined) params.set('min_diskon', String(minDiskon));
    if (maxDiskon !== null && maxDiskon !== undefined) params.set('max_diskon', String(maxDiskon));
    return params.toString();
  }

  async function handleSoftDelete() {
    if (!deleteTx || deleteReason.trim().length < 5) return;
    setIsDeleting(true);
    try {
      const res = await fetch(`/transaksi/${deleteTx.id}/soft-delete`, {
        method: 'DELETE',
        headers: {
          'Content-Type':  'application/json',
          'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        },
        body: JSON.stringify({ reason: deleteReason }),
      });
      const json = await res.json();
      if (res.ok) {
        setDeleteOpen(false);
        setDeleteTx(null);
        setDeleteReason('');
        router.reload({ only: ['transaksis'] });
      } else {
        alert(json.error ?? 'Gagal menghapus transaksi');
      }
    } catch {
      alert('Terjadi kesalahan jaringan');
    } finally {
      setIsDeleting(false);
    }
  }

  // ── Render ──────────────────────────────────────────────────────────────────

  return (
    <AppLayout breadcrumbs={[{ title: 'Transaksi', href: '/transaksi' }]}>
      <Head title="Transaksi" />

      <div className="space-y-5">

        {/* Header */}
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-xl font-semibold">Daftar Transaksi</h1>
            <p className="text-sm text-muted-foreground">
              {transaksis?.total ?? 0} transaksi &bull; {periodLabel}
            </p>
          </div>
          {isItSupport && (
            <Link href="/admin/deleted-transactions">
              <Button variant="outline" size="sm">
                <Trash2 className="mr-2 h-4 w-4" />
                Lihat Data Terhapus
              </Button>
            </Link>
          )}
        </div>

        {/* Filter card */}
        <div className="rounded-xl border bg-card p-5 shadow-sm space-y-4">

          {/* Preset pills */}
          <div className="space-y-2">
            <p className="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
              <Zap className="h-3.5 w-3.5" /> Periode
            </p>
            <div className="flex flex-wrap gap-2">
              {QUICK_RANGES.map((opt) => (
                <button
                  key={opt.value}
                  type="button"
                  disabled={isLoading}
                  onClick={() => handlePresetClick(opt.value)}
                  className={[
                    'rounded-full border px-4 py-1.5 text-sm font-medium transition-all',
                    'disabled:cursor-not-allowed disabled:opacity-50',
                    quickRange === opt.value
                      ? 'border-primary bg-primary text-primary-foreground shadow-sm'
                      : 'border-border bg-background hover:border-primary/60 hover:bg-muted',
                  ].join(' ')}
                >
                  {opt.label}
                </button>
              ))}
            </div>
          </div>

          {/* Custom date picker — hanya muncul saat preset 'kustom' */}
          {quickRange === 'kustom' && (
            <div className="rounded-lg border border-dashed border-primary/40 bg-primary/5 p-4 space-y-3">
              <p className="flex items-center gap-1.5 text-sm font-medium text-primary">
                <CalendarRange className="h-4 w-4" /> Rentang Tanggal Kustom
              </p>
              <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div className="space-y-1">
                  <label className="text-xs text-muted-foreground">Tanggal Mulai</label>
                  <input
                    type="date"
                    className="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    value={customMulai}
                    max={today}
                    onChange={(e) => { setCustomMulai(e.target.value); setDateError(null); }}
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-xs text-muted-foreground">Tanggal Selesai</label>
                  <input
                    type="date"
                    className="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    value={customSelesai}
                    min={customMulai}
                    max={today}
                    onChange={(e) => { setCustomSelesai(e.target.value); setDateError(null); }}
                  />
                </div>
              </div>
              {dateError && (
                <p className="flex items-center gap-1 text-xs text-destructive">
                  <AlertCircle className="h-3 w-3" /> {dateError}
                </p>
              )}
            </div>
          )}

          {/* Status, per halaman, actions */}
          <div className="flex flex-wrap items-end gap-3 border-t pt-4">
            <div className="min-w-[14rem] flex-1 space-y-1">
              <label className="text-xs font-medium text-muted-foreground">Pencarian</label>
              <Input
                className="h-9"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Invoice / nama pelanggan / nominal..."
                onKeyDown={(e) => {
                  if (e.key === 'Enter') handleSubmit();
                }}
              />
            </div>

            <div className="min-w-[10rem] space-y-1">
              <label className="text-xs font-medium text-muted-foreground">Status</label>
              <Select value={status || '__all__'} onValueChange={(v) => setStatus(v === '__all__' ? '' : v)}>
                <SelectTrigger className="h-9"><SelectValue placeholder="Semua status" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="__all__">Semua status</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="selesai">Selesai</SelectItem>
                  <SelectItem value="batal">Batal</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="min-w-[12rem] space-y-1">
              <label className="text-xs font-medium text-muted-foreground">Diskon</label>
              <Select value={diskonStatus || '__all__'} onValueChange={(v) => setDiskonStatus(v === '__all__' ? '' : v)}>
                <SelectTrigger className="h-9"><SelectValue placeholder="Semua" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="__all__">Semua</SelectItem>
                  <SelectItem value="discounted">Berdiskon</SelectItem>
                  <SelectItem value="no_discount">Tanpa Diskon</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="min-w-[12rem] space-y-1">
              <label className="text-xs font-medium text-muted-foreground">Sorting</label>
              <Select value={`${sortBy}:${sortDir}`} onValueChange={(v) => {
                const [by, dir] = v.split(':');
                setSortBy(by);
                setSortDir(dir);
              }}>
                <SelectTrigger className="h-9"><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="tanggal:desc">Tanggal terbaru</SelectItem>
                  <SelectItem value="tanggal:asc">Tanggal terlama</SelectItem>
                  <SelectItem value="diskon:desc">Diskon terbesar</SelectItem>
                  <SelectItem value="diskon:asc">Diskon terkecil</SelectItem>
                  <SelectItem value="total:desc">Total terbesar</SelectItem>
                  <SelectItem value="total:asc">Total terkecil</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="w-40 space-y-1">
              <label className="text-xs font-medium text-muted-foreground">Min diskon (Rp)</label>
              <Input
                className="h-9"
                inputMode="numeric"
                value={minDiskon ?? ''}
                onChange={(e) => {
                  const v = e.target.value;
                  const n = v === '' ? null : Number(v);
                  setMinDiskon(n === null || Number.isFinite(n) ? n : null);
                }}
                placeholder="0"
              />
            </div>

            <div className="w-40 space-y-1">
              <label className="text-xs font-medium text-muted-foreground">Max diskon (Rp)</label>
              <Input
                className="h-9"
                inputMode="numeric"
                value={maxDiskon ?? ''}
                onChange={(e) => {
                  const v = e.target.value;
                  const n = v === '' ? null : Number(v);
                  setMaxDiskon(n === null || Number.isFinite(n) ? n : null);
                }}
                placeholder="0"
              />
            </div>

            <div className="w-28 space-y-1">
              <label className="text-xs font-medium text-muted-foreground">Per halaman</label>
              <Select value={perPage} onValueChange={setPerPage}>
                <SelectTrigger className="h-9"><SelectValue /></SelectTrigger>
                <SelectContent>
                  {['15','25','50','100'].map((v) => (
                    <SelectItem key={v} value={v}>{v}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="flex gap-2">
              <Button
                type="button"
                className="h-9"
                disabled={isLoading}
                onClick={handleSubmit}
              >
                {isLoading
                  ? <><span className="mr-2 h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent inline-block" />Memuat...</>
                  : 'Terapkan'}
              </Button>
              <Button type="button" variant="outline" className="h-9" disabled={isLoading} onClick={handleReset}>
                Reset
              </Button>
            </div>

            <div className="ml-auto flex gap-2">
              <Button
                type="button" variant="outline" size="sm" className="h-9"
                onClick={() => {
                  const params = buildExportParams();
                  if (params !== null) window.open(`/transaksi/export-pdf?${params}`, '_blank');
                }}
              >
                Export PDF
              </Button>
              <Button
                type="button" variant="outline" size="sm" className="h-9"
                onClick={() => {
                  const params = buildExportParams();
                  if (params !== null) window.open(`/transaksi/export-excel?${params}`, '_blank');
                }}
              >
                Export Excel
              </Button>
            </div>
          </div>
        </div>

        {/* Tabel */}
        <div className="rounded-xl border bg-card shadow-sm overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="border-b bg-muted/50">
                <tr>
                  {[
                    { label: 'Invoice', cls: 'text-left' },
                    { label: 'Cabang',  cls: 'text-left' },
                    { label: 'Kasir',   cls: 'text-left' },
                    { label: 'Pelanggan', cls: 'text-left' },
                    { label: 'Diskon',  cls: 'text-left' },
                    { label: 'Total',   cls: 'text-right' },
                    { label: 'Status',  cls: 'text-left' },
                    { label: 'Waktu',   cls: 'text-left' },
                    { label: 'Aksi',    cls: 'text-left' },
                  ].map((h) => (
                    <th key={h.label} className={`py-3 px-4 font-medium text-muted-foreground whitespace-nowrap ${h.cls}`}>
                      {h.label}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y">
                {(transaksis?.data ?? []).map((t) => (
                  <tr key={t.id} className="hover:bg-muted/20 transition-colors">
                    <td className="py-3 px-4 font-mono text-xs text-muted-foreground">
                      {t.nomor_invoice ?? `#${t.id}`}
                    </td>
                    <td className="py-3 px-4">{t.cabang?.nama ?? t.cabang?.kode ?? '-'}</td>
                    <td className="py-3 px-4">{t.user?.name ?? '-'}</td>
                    <td className="py-3 px-4">
                      <div className="max-w-[16rem] truncate text-xs text-muted-foreground" title={t.nama_pelanggan ?? ''}>
                        {t.nama_pelanggan ? t.nama_pelanggan : '-'}
                      </div>
                    </td>
                    <td className="py-3 px-4">
                      {(() => {
                        const diskonNominal = parseFloat(String(t.diskon ?? 0));
                        const isDiskon = !Number.isNaN(diskonNominal) && diskonNominal > 0;
                        const persenRaw = t.diskon_persen !== null && t.diskon_persen !== undefined ? parseFloat(String(t.diskon_persen)) : null;
                        const subtotal = parseFloat(String(t.subtotal ?? 0));
                        const persen = persenRaw !== null && !Number.isNaN(persenRaw)
                          ? persenRaw
                          : (subtotal > 0 && isDiskon ? (diskonNominal / subtotal) * 100 : 0);

                        if (!isDiskon) {
                          return (
                            <span className="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground">
                              Tanpa Diskon
                            </span>
                          );
                        }

                        return (
                          <div className="space-y-0.5">
                            <span className="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                              Berdiskon
                            </span>
                            <div className="text-xs text-muted-foreground">
                              Rp {formatCurrency(diskonNominal)} ({Number.isFinite(persen) ? persen.toFixed(2) : '0.00'}%)
                            </div>
                          </div>
                        );
                      })()}
                    </td>
                    <td className="py-3 px-4 text-right font-medium tabular-nums">
                      Rp {formatCurrency(t.total)}
                    </td>
                    <td className="py-3 px-4">
                      <span className={[
                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                        STATUS_BADGE[t.status ?? ''] ?? 'bg-muted text-muted-foreground',
                      ].join(' ')}>
                        {t.status ?? '-'}
                      </span>
                    </td>
                    <td className="py-3 px-4 text-xs text-muted-foreground whitespace-nowrap">
                      {formatDateTime(t.created_at)}
                    </td>
                    <td className="py-3 px-4">
                      <div className="flex items-center gap-2">
                        <Link
                          href={`/transaksi/${t.id}/show`}
                          className="text-xs text-primary underline underline-offset-2 hover:no-underline"
                        >
                          Detail
                        </Link>
                        {isItSupport && (
                          <>
                            <span className="select-none text-border">|</span>
                            <button
                              onClick={() => { setDeleteTx(t); setDeleteOpen(true); }}
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

                {(transaksis?.data ?? []).length === 0 && (
                  <tr>
                    <td colSpan={9} className="py-20 text-center">
                      <div className="flex flex-col items-center gap-2 text-muted-foreground">
                        <CalendarRange className="h-10 w-10 opacity-20" />
                        <p className="text-sm font-medium">Tidak ada transaksi</p>
                        <p className="text-xs opacity-60">{periodLabel}</p>
                      </div>
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>

          <div className="flex items-center justify-between border-t px-4 py-3 text-sm text-muted-foreground">
            <span>Halaman {transaksis?.current_page ?? 1} dari {transaksis?.last_page ?? 1}</span>
            <div className="flex gap-2">
              <Button asChild variant="outline" size="sm" disabled={!transaksis?.prev_page_url}>
                <Link href={transaksis?.prev_page_url ?? '#'}>← Sebelumnya</Link>
              </Button>
              <Button asChild variant="outline" size="sm" disabled={!transaksis?.next_page_url}>
                <Link href={transaksis?.next_page_url ?? '#'}>Berikutnya →</Link>
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
            <DialogTitle>Hapus Transaksi</DialogTitle>
            <DialogDescription asChild>
              <div className="mt-1 space-y-3 text-sm">
                <div className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 rounded-lg bg-muted p-3 text-xs">
                  <span className="text-muted-foreground">Invoice</span>
                  <span className="font-mono font-medium">{deleteTx?.nomor_invoice ?? `#${deleteTx?.id}`}</span>
                  <span className="text-muted-foreground">Total</span>
                  <span className="font-medium">Rp {formatCurrency(deleteTx?.total)}</span>
                </div>
                <Alert variant="destructive" className="py-2 text-xs">
                  <AlertCircle className="h-3.5 w-3.5" />
                  <AlertDescription>
                    Transaksi akan di-soft delete dan dapat dikembalikan lewat menu "Lihat Terhapus".
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
            <Button variant="outline" onClick={() => { setDeleteOpen(false); setDeleteReason(''); }}>
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
