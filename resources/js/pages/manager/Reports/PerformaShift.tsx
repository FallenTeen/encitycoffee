import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Head, Link, router, useForm } from '@inertiajs/react';
import Swal from 'sweetalert2';

interface ShiftPerformance {
  shift_id: number;
  kasir: string | null;
  cabang: string | null;
  waktu_buka: string;
  waktu_tutup: string | null;
  total_penjualan: number;
  jumlah_transaksi: number;
}

interface Filters {
  tanggal_mulai: string;
  tanggal_selesai: string;
  cabang_id?: number | null;
}

interface CabangOption {
  id: number;
  kode?: string | null;
  nama?: string | null;
}

interface Props {
  performance: ShiftPerformance[];
  filters: Filters;
  cabangOptions: CabangOption[];
}

function formatRupiah(value: number | string | null | undefined) {
  const num = typeof value === 'string' ? Number(value) : value ?? 0;
  if (!Number.isFinite(num)) return '-';
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(num);
}

export default function ManagerPerformaShift({ performance, filters, cabangOptions }: Props) {
  const { data, setData, get, processing } = useForm({
    tanggal_mulai: filters?.tanggal_mulai ?? '',
    tanggal_selesai: filters?.tanggal_selesai ?? '',
    cabang_id: filters?.cabang_id ? String(filters.cabang_id) : '',
  });

  const submit = () => {
    get('/manager/performa-shift', {
      preserveScroll: true,
      preserveState: true,
      replace: true,
      only: ['performance', 'filters', 'cabangOptions'],
    });
  };

  const totalShift = performance?.length ?? 0;
  const totalPenjualan = (performance ?? []).reduce(
    (sum, row) => sum + (row.total_penjualan ?? 0),
    0,
  );
  const totalTransaksi = (performance ?? []).reduce(
    (sum, row) => sum + (row.jumlah_transaksi ?? 0),
    0,
  );
  const shiftAktif = (performance ?? []).filter((row) => !row.waktu_tutup).length;

  const handleCloseShift = async (shiftId: number) => {
    const result = await Swal.fire({
      title: 'Tutup Shift',
      text: 'Masukkan saldo akhir untuk menutup shift ini.',
      icon: 'warning',
      input: 'number',
      inputLabel: 'Saldo akhir',
      inputAttributes: {
        min: '0',
        step: '1000',
      },
      showCancelButton: true,
      confirmButtonText: 'Tutup Shift',
      cancelButtonText: 'Batal',
      reverseButtons: true,
      focusCancel: true,
      preConfirm: (value) => {
        if (value === null || value === undefined || String(value).trim() === '') {
          Swal.showValidationMessage('Saldo akhir wajib diisi');
          return false;
        }
        const num = Number(value);
        if (!Number.isFinite(num) || num < 0) {
          Swal.showValidationMessage('Saldo akhir tidak valid');
          return false;
        }
        return num;
      },
    });

    if (!result.isConfirmed) {
      return;
    }

    const saldoAkhir = Number(result.value);

    router.post(
      `/pos/shift/${shiftId}/tutup`,
      { saldo_akhir: saldoAkhir },
      {
        preserveScroll: true,
        onSuccess: () => {
          Swal.fire('Berhasil', 'Shift berhasil ditutup.', 'success');
        },
        onError: () => {
          Swal.fire('Gagal', 'Gagal menutup shift. Silakan coba lagi.', 'error');
        },
      },
    );
  };

  return (
    <AppLayout breadcrumbs={[{ title: 'Performa Shift', href: '/manager/performa-shift' }]}>
      <Head title="Performa Shift" />
      <div className="space-y-6">
        <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
          <div>
            <h1 className="text-xl font-semibold">Performa Shift</h1>
            <p className="text-sm text-muted-foreground">
              Performa per shift berdasarkan transaksi dan pendapatan.
            </p>
          </div>
        </div>

        <div className="rounded-md border p-4">
          <form
            className="grid grid-cols-1 gap-4 md:grid-cols-4"
            onSubmit={(e) => {
              e.preventDefault();
              submit();
            }}
          >
            <div className="space-y-1">
              <Label htmlFor="tanggal_mulai">Tanggal Mulai</Label>
              <Input
                id="tanggal_mulai"
                type="date"
                value={data.tanggal_mulai}
                onChange={(e) => setData('tanggal_mulai', e.target.value)}
              />
            </div>
            <div className="space-y-1">
              <Label htmlFor="tanggal_selesai">Tanggal Selesai</Label>
              <Input
                id="tanggal_selesai"
                type="date"
                value={data.tanggal_selesai}
                onChange={(e) => setData('tanggal_selesai', e.target.value)}
              />
            </div>
            <div className="space-y-1">
              <Label htmlFor="cabang_id">Cabang</Label>
              <Select
                value={data.cabang_id}
                onValueChange={(v) => setData('cabang_id', v)}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Semua cabang" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">Semua cabang</SelectItem>
                  {cabangOptions.map((c) => (
                    <SelectItem key={c.id} value={String(c.id)}>
                      {(c.kode ?? c.id) + ' - ' + (c.nama ?? '-')}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="flex items-end gap-2">
              <Button type="submit" size="sm" disabled={processing}>
                Terapkan
              </Button>
              <Button
                type="button"
                size="sm"
                variant="secondary"
                onClick={() => {
                  setData({
                    tanggal_mulai: '',
                    tanggal_selesai: '',
                    cabang_id: '',
                  });
                  get('/manager/performa-shift', {
                    preserveScroll: true,
                    preserveState: false,
                    replace: true,
                    only: ['performance', 'filters', 'cabangOptions'],
                  });
                }}
              >
                Reset
              </Button>
            </div>
          </form>
        </div>

        <div className="grid gap-4 md:grid-cols-4">
          <div className="rounded-md border p-4">
            <div className="text-xs text-muted-foreground">Total Shift</div>
            <div className="text-xl font-semibold">{totalShift}</div>
          </div>
          <div className="rounded-md border p-4">
            <div className="text-xs text-muted-foreground">Shift Aktif</div>
            <div className="text-xl font-semibold">{shiftAktif}</div>
          </div>
          <div className="rounded-md border p-4">
            <div className="text-xs text-muted-foreground">Total Penjualan</div>
            <div className="text-xl font-semibold">{formatRupiah(totalPenjualan)}</div>
          </div>
          <div className="rounded-md border p-4">
            <div className="text-xs text-muted-foreground">Total Transaksi</div>
            <div className="text-xl font-semibold">{totalTransaksi}</div>
          </div>
        </div>

        <div className="rounded-md border">
          <div className="overflow-x-auto">
            <table className="min-w-full text-xs">
              <thead>
                <tr className="border-b">
                  <th className="py-2 px-4 text-left">Shift</th>
                  <th className="py-2 px-4 text-left">Cabang</th>
                  <th className="py-2 px-4 text-left">Kasir</th>
                  <th className="py-2 px-4 text-left">Waktu Buka</th>
                  <th className="py-2 px-4 text-left">Waktu Tutup</th>
                  <th className="py-2 px-4 text-right">Transaksi</th>
                  <th className="py-2 px-4 text-right">Total Penjualan</th>
                  <th className="py-2 px-4 text-left">Status</th>
                  <th className="py-2 px-4 text-left">Aksi</th>
                </tr>
              </thead>
              <tbody>
                {performance.map((row) => {
                  const isOpen = !row.waktu_tutup;
                  return (
                    <tr key={row.shift_id} className="border-b last:border-0">
                      <td className="py-2 px-4 align-top font-mono text-xs">
                        #{row.shift_id}
                      </td>
                      <td className="py-2 px-4 align-top">
                        <div className="text-xs font-medium">
                          {row.cabang ?? '-'}
                        </div>
                      </td>
                      <td className="py-2 px-4 align-top">
                        <div className="text-xs">{row.kasir ?? '-'}</div>
                      </td>
                      <td className="py-2 px-4 align-top">
                        <div className="text-xs">
                          {row.waktu_buka
                            ? new Date(row.waktu_buka).toLocaleString('id-ID')
                            : '-'}
                        </div>
                      </td>
                      <td className="py-2 px-4 align-top">
                        <div className="text-xs">
                          {row.waktu_tutup
                            ? new Date(row.waktu_tutup).toLocaleString('id-ID')
                            : '-'}
                        </div>
                      </td>
                      <td className="py-2 px-4 align-top text-right">
                        {row.jumlah_transaksi}
                      </td>
                      <td className="py-2 px-4 align-top text-right">
                        {formatRupiah(row.total_penjualan)}
                      </td>
                      <td className="py-2 px-4 align-top">
                        <span className="inline-flex items-center rounded bg-muted px-2 py-0.5 text-[10px] text-muted-foreground">
                          {isOpen ? 'Aktif' : 'Tutup'}
                        </span>
                      </td>
                      <td className="py-2 px-4 align-top">
                        <div className="flex flex-wrap gap-2 text-xs">
                          <Link
                            href={`/laporan/shift/${row.shift_id}`}
                            className="underline text-muted-foreground"
                          >
                            Detail
                          </Link>
                          {isOpen && (
                            <button
                              type="button"
                              className="text-destructive underline"
                              onClick={() => handleCloseShift(row.shift_id)}
                            >
                              Tutup shift
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })}
                {performance.length === 0 && (
                  <tr>
                    <td
                      colSpan={9}
                      className="px-4 py-8 text-center text-xs text-muted-foreground"
                    >
                      Belum ada data shift untuk periode dan cabang yang dipilih.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
