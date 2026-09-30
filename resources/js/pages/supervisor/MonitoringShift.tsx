import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import supervisor from '@/routes/supervisor';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import Swal from 'sweetalert2';

interface Props {
  shift: {
    data: Array<{
      id: number;
      user?: { id: number; name?: string; email?: string } | null;
      cabang?: { id: number; kode?: string; nama?: string } | null;
      waktu_buka?: string | null;
      waktu_tutup?: string | null;
      status?: string | null;
      total_transaksi?: number;
      total_penjualan?: number;
      durasi_shift_menit?: number | null;
      status_selisih?: string | null;
    }>;
    total: number;
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
  };
  filter_aktif: { status?: string; tanggal?: string; cabang_id?: string | number; user_id?: string | number };
  statistik_ringkasan: { total_shift: number; total_transaksi: number; total_penjualan: number };
  cabang_list: Array<{ id: number; kode?: string; nama?: string }>;
}

function formatDateTime(value?: string | null) {
  if (!value) return '-';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' });
}

function formatCurrency(value?: number) {
  const n = typeof value === 'number' ? value : 0;
  return n.toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' });
}

export default function MonitoringShift({ shift, filter_aktif, statistik_ringkasan, cabang_list }: Props) {
  const [autoRefresh, setAutoRefresh] = useState(false);

  const { data, setData, get, processing, errors } = useForm({
    status: filter_aktif?.status ?? '',
    tanggal: filter_aktif?.tanggal ?? '',
    cabang_id: filter_aktif?.cabang_id ? String(filter_aktif.cabang_id) : '',
    user_id: filter_aktif?.user_id ? String(filter_aktif.user_id) : '',
  });

  const cabangOptions = useMemo(
    () =>
      [{ id: 0, kode: '-', nama: 'Semua cabang' }].concat(
        (cabang_list ?? []).map((c) => ({
          id: c.id,
          kode: c.kode ?? String(c.id),
          nama: c.nama ?? '-',
        })),
      ),
    [cabang_list],
  );

  const handleOpenShift = async () => {
    if (!data.cabang_id) {
      await Swal.fire('Pilih cabang', 'Silakan pilih cabang pada filter terlebih dahulu.', 'info');
      return;
    }

    const result = await Swal.fire({
      title: 'Buka Shift',
      text: 'Masukkan saldo awal untuk shift baru.',
      icon: 'warning',
      input: 'number',
      inputLabel: 'Saldo awal',
      inputAttributes: {
        min: '0',
        step: '1000',
      },
      showCancelButton: true,
      confirmButtonText: 'Buka Shift',
      cancelButtonText: 'Batal',
      reverseButtons: true,
      focusCancel: true,
      preConfirm: (value) => {
        if (value === null || value === undefined || String(value).trim() === '') {
          Swal.showValidationMessage('Saldo awal wajib diisi');
          return false;
        }
        const num = Number(value);
        if (!Number.isFinite(num) || num < 0) {
          Swal.showValidationMessage('Saldo awal tidak valid');
          return false;
        }
        return num;
      },
    });

    if (!result.isConfirmed) {
      return;
    }

    const saldoAwal = Number(result.value);
    const cabangId = Number(data.cabang_id);

    router.post(
      '/pos/shift/buka',
      { saldo_awal: saldoAwal, cabang_id: cabangId },
      {
        preserveScroll: true,
        onSuccess: () => {
          Swal.fire('Berhasil', 'Shift berhasil dibuka.', 'success');
          router.reload({
            preserveUrl: true,
            only: ['shift', 'statistik_ringkasan'],
          });
        },
        onError: () => {
          Swal.fire('Gagal', 'Gagal membuka shift. Silakan coba lagi.', 'error');
        },
      },
    );
  };

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
          router.reload({
            preserveUrl: true,
            only: ['shift', 'statistik_ringkasan'],
          });
        },
        onError: () => {
          Swal.fire('Gagal', 'Gagal menutup shift. Silakan coba lagi.', 'error');
        },
      },
    );
  };

  useEffect(() => {
    if (!autoRefresh) return;
    const id = window.setInterval(() => {
      router.reload({
        preserveUrl: true,
        only: ['shift', 'statistik_ringkasan'],
      });
    }, 15000);
    return () => window.clearInterval(id);
  }, [autoRefresh]);

  return (
    <AppLayout title="Monitoring Shift">
      <Head title="Supervisor - Monitoring Shift" />
      <div className="space-y-6">
        <div className="grid gap-4 md:grid-cols-3">
          <div className="rounded-md border bg-card p-4">
            <div className="text-sm text-muted-foreground">Total Shift</div>
            <div className="mt-1 text-2xl font-bold">{statistik_ringkasan?.total_shift ?? 0}</div>
          </div>
          <div className="rounded-md border bg-card p-4">
            <div className="text-sm text-muted-foreground">Total Transaksi</div>
            <div className="mt-1 text-2xl font-bold">{statistik_ringkasan?.total_transaksi ?? 0}</div>
          </div>
          <div className="rounded-md border bg-card p-4">
            <div className="text-sm text-muted-foreground">Total Penjualan</div>
            <div className="mt-1 text-2xl font-bold">Rp {formatCurrency(statistik_ringkasan?.total_penjualan ?? 0)}</div>
          </div>
        </div>

        <div className="rounded-md border p-4">
          <form
            className="grid grid-cols-1 gap-4 md:grid-cols-5"
            onSubmit={(e) => {
              e.preventDefault();
              get(supervisor.monitoring.shift().url, { preserveScroll: true, preserveState: true, replace: true });
            }}
          >
            <div className="space-y-1">
              <Label>Status</Label>
              <Select value={data.status} onValueChange={(v) => setData('status', v === '__all__' ? '' : v)}>
                <SelectTrigger>
                  <SelectValue placeholder="Semua status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="__all__">Semua status</SelectItem>
                  <SelectItem value="open">Open</SelectItem>
                  <SelectItem value="closed">Closed</SelectItem>
                </SelectContent>
              </Select>
              <InputError message={errors.status} />
            </div>

            <div className="space-y-1">
              <Label htmlFor="tanggal">Tanggal</Label>
              <Input id="tanggal" type="date" value={data.tanggal} onChange={(e) => setData('tanggal', e.target.value)} />
              <InputError message={errors.tanggal} />
            </div>

            <div className="space-y-1">
              <Label>Cabang</Label>
              <Select value={data.cabang_id} onValueChange={(v) => setData('cabang_id', v === '0' ? '' : v)}>
                <SelectTrigger>
                  <SelectValue placeholder="Semua cabang" />
                </SelectTrigger>
                <SelectContent>
                  {cabangOptions.map((c) => (
                    <SelectItem key={c.id} value={String(c.id)}>
                      {(c.kode ?? c.id) + ' - ' + (c.nama ?? '-')}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={errors.cabang_id as unknown as string} />
            </div>

            <div className="space-y-1">
              <Label htmlFor="user_id">User ID</Label>
              <Input
                id="user_id"
                inputMode="numeric"
                value={data.user_id}
                onChange={(e) => setData('user_id', e.target.value)}
                placeholder="Opsional"
              />
              <InputError message={errors.user_id} />
            </div>

            <div className="flex items-end gap-2">
              <Button type="submit" disabled={processing}>
                Terapkan
              </Button>
              <Button
                type="button"
                variant="secondary"
                onClick={() => {
                  setData({ status: '', tanggal: '', cabang_id: '', user_id: '' });
                  router.get(supervisor.monitoring.shift().url, {}, { preserveScroll: true, replace: true });
                }}
              >
                Reset
              </Button>
            </div>

            <div className="md:col-span-5 flex items-center justify-between border-t pt-4">
              <div className="flex items-center gap-2">
                <Checkbox checked={autoRefresh} onCheckedChange={(v) => setAutoRefresh(Boolean(v))} />
                <span className="text-sm text-muted-foreground">Auto refresh (15s)</span>
              </div>
              <div className="flex items-center gap-2">
                <Button type="button" size="sm" onClick={handleOpenShift}>
                  Buka shift
                </Button>
                <Button
                  type="button"
                  size="sm"
                  variant="secondary"
                  onClick={() =>
                    router.reload({ preserveUrl: true, only: ['shift', 'statistik_ringkasan'] })
                  }
                >
                  Refresh
                </Button>
              </div>
            </div>
          </form>
        </div>

        <div className="rounded-md border">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left">
                  <th className="py-2 px-4">Cabang</th>
                  <th className="py-2 px-4">Kasir</th>
                  <th className="py-2 px-4">Waktu Buka</th>
                  <th className="py-2 px-4">Waktu Tutup</th>
                  <th className="py-2 px-4">Status</th>
                  <th className="py-2 px-4">Transaksi</th>
                  <th className="py-2 px-4">Penjualan</th>
                  <th className="py-2 px-4">Selisih</th>
                  <th className="py-2 px-4">Aksi</th>
                </tr>
              </thead>
              <tbody>
                {(shift?.data ?? []).map((s) => (
                  <tr key={s.id} className="border-b last:border-0">
                    <td className="py-2 px-4">{s.cabang?.nama ?? s.cabang?.kode ?? '-'}</td>
                    <td className="py-2 px-4">{s.user?.name ?? '-'}</td>
                    <td className="py-2 px-4">{formatDateTime(s.waktu_buka)}</td>
                    <td className="py-2 px-4">{formatDateTime(s.waktu_tutup)}</td>
                    <td className="py-2 px-4">
                      <span className="inline-flex items-center rounded bg-muted px-2 py-0.5 text-muted-foreground">
                        {s.status ?? '-'}
                      </span>
                    </td>
                    <td className="py-2 px-4">{s.total_transaksi ?? 0}</td>
                    <td className="py-2 px-4">Rp {formatCurrency(s.total_penjualan ?? 0)}</td>
                    <td className="py-2 px-4">{s.status_selisih ?? '-'}</td>
                    <td className="py-2 px-4">
                      <div className="flex flex-wrap gap-2">
                        <Link href={supervisor.shift.detail(s.id)} className="text-primary underline">
                          Detail
                        </Link>
                        {s.status === 'buka' && (
                          <button
                            type="button"
                            className="text-destructive underline"
                            onClick={() => handleCloseShift(s.id)}
                          >
                            Tutup shift
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
                {(shift?.data ?? []).length === 0 && (
                  <tr>
                    <td colSpan={9} className="px-4 py-8 text-center text-muted-foreground">
                      Belum ada data shift.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
          <div className="flex items-center justify-between border-t p-4 text-sm">
            <div>
              Halaman {shift?.current_page ?? 1} / {shift?.last_page ?? 1}
            </div>
            <div className="flex gap-2">
              <Button asChild variant="secondary" disabled={!shift?.prev_page_url}>
                <Link href={shift?.prev_page_url ?? supervisor.monitoring.shift()}>Sebelumnya</Link>
              </Button>
              <Button asChild variant="secondary" disabled={!shift?.next_page_url}>
                <Link href={shift?.next_page_url ?? supervisor.monitoring.shift()}>Berikutnya</Link>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
