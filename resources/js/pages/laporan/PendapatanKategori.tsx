import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Head, router, useForm } from '@inertiajs/react';
import { useMemo } from 'react';

interface KategoriRow {
  kategori_id: number;
  kategori: string;
  pendapatan_kotor: number;
  total_modal: number;
  margin: number;
  margin_persen: number;
}

interface Ringkasan {
  total_pendapatan_kotor: number;
  total_modal: number;
  total_margin: number;
  rata_rata_margin_per_kategori: number;
  kategori_margin_tertinggi?: KategoriRow | null;
  kategori_margin_terendah?: KategoriRow | null;
}

interface FilterAktif {
  tanggal_mulai?: string | null;
  tanggal_selesai?: string | null;
  kategori_id?: number | null;
  harga_min?: number | null;
  harga_max?: number | null;
}

interface KategoriOption {
  id: number;
  nama: string;
}

interface Props {
  kategori: KategoriRow[];
  ringkasan: Ringkasan;
  filter_aktif?: FilterAktif;
  kategori_options: KategoriOption[];
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

export default function PendapatanKategoriPage({
  kategori,
  ringkasan,
  filter_aktif,
  kategori_options,
}: Props) {
  const { data, setData, get, processing, errors } = useForm({
    tanggal_mulai: filter_aktif?.tanggal_mulai ?? '',
    tanggal_selesai: filter_aktif?.tanggal_selesai ?? '',
    kategori_id: filter_aktif?.kategori_id ? String(filter_aktif.kategori_id) : '',
    harga_min:
      filter_aktif?.harga_min !== undefined && filter_aktif?.harga_min !== null
        ? String(filter_aktif.harga_min)
        : '',
    harga_max:
      filter_aktif?.harga_max !== undefined && filter_aktif?.harga_max !== null
        ? String(filter_aktif.harga_max)
        : '',
  });

  const rows = useMemo(() => {
    return (kategori ?? []).map((row) => {
      const pendapatanKotor = Number(row.pendapatan_kotor ?? 0);
      const totalModal = Number(row.total_modal ?? 0);
      const margin = Number(row.margin ?? 0);
      const pendapatanBersih = pendapatanKotor - totalModal;
      return {
        ...row,
        pendapatan_kotor: pendapatanKotor,
        total_modal: totalModal,
        margin,
        pendapatan_bersih: pendapatanBersih,
      };
    });
  }, [kategori]);

  const maxValue = useMemo(() => {
    if (rows.length === 0) return 0;
    return rows.reduce((max, row: any) => {
      const localMax = Math.max(
        row.pendapatan_kotor,
        row.total_modal,
        row.margin,
        row.pendapatan_bersih
      );
      return localMax > max ? localMax : max;
    }, 0);
  }, [rows]);

  const submit = () => {
    get('/laporan/pendapatan-kategori', {
      preserveScroll: true,
      preserveState: true,
      replace: true,
      only: ['kategori', 'ringkasan', 'filter_aktif', 'kategori_options'],
    });
  };

  return (
    <AppLayout
      breadcrumbs={[
        { title: 'Laporan', href: '/laporan/shift' },
        { title: 'Pendapatan per Kategori', href: '/laporan/pendapatan-kategori' },
      ]}
    >
      <Head title="Pendapatan per Kategori" />
      <div className="space-y-6">
        <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
          <div>
            <h1 className="text-xl font-semibold">Pendapatan per Kategori Produk</h1>
            <p className="text-sm text-muted-foreground">
              Analitik pendapatan kotor, modal, dan margin keuntungan per kategori produk.
            </p>
          </div>
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={() => {
              router.get('/laporan/pendapatan-kategori', {}, { preserveScroll: true, replace: true });
              setData({
                tanggal_mulai: '',
                tanggal_selesai: '',
                kategori_id: '',
                harga_min: '',
                harga_max: '',
              });
            }}
          >
            Reset Filter
          </Button>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Filter Analitik</CardTitle>
          </CardHeader>
          <CardContent>
            <form
              className="grid grid-cols-1 gap-4 md:grid-cols-5"
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
                <InputError message={errors.tanggal_mulai as string} />
              </div>
              <div className="space-y-1">
                <Label htmlFor="tanggal_selesai">Tanggal Selesai</Label>
                <Input
                  id="tanggal_selesai"
                  type="date"
                  value={data.tanggal_selesai}
                  onChange={(e) => setData('tanggal_selesai', e.target.value)}
                />
                <InputError message={errors.tanggal_selesai as string} />
              </div>
              <div className="space-y-1">
                <Label htmlFor="kategori_id">Kategori Produk</Label>
                <Select
                  value={data.kategori_id}
                  onValueChange={(value) => setData('kategori_id', value)}
                >
                  <SelectTrigger id="kategori_id">
                    <SelectValue placeholder="Semua kategori" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">Semua kategori</SelectItem>
                    {(kategori_options ?? []).map((k) => (
                      <SelectItem key={k.id} value={String(k.id)}>
                        {k.nama}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <InputError message={errors.kategori_id as string} />
              </div>
              <div className="space-y-1">
                <Label htmlFor="harga_min">Harga Minimum</Label>
                <Input
                  id="harga_min"
                  type="number"
                  min={0}
                  value={data.harga_min}
                  onChange={(e) => setData('harga_min', e.target.value)}
                  placeholder="Contoh: 20000"
                />
                <InputError message={errors.harga_min as string} />
              </div>
              <div className="space-y-1">
                <Label htmlFor="harga_max">Harga Maksimum</Label>
                <Input
                  id="harga_max"
                  type="number"
                  min={0}
                  value={data.harga_max}
                  onChange={(e) => setData('harga_max', e.target.value)}
                  placeholder="Contoh: 50000"
                />
                <InputError message={errors.harga_max as string} />
              </div>
              <div className="md:col-span-5 flex items-end justify-end gap-2 pt-1">
                <Button type="submit" disabled={processing}>
                  Terapkan
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>

        <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
          <Card>
            <CardHeader>
              <CardTitle className="text-sm font-medium">Total Pendapatan Kotor</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-xl font-semibold">
                {formatRupiah(ringkasan?.total_pendapatan_kotor ?? 0)}
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle className="text-sm font-medium">Total Modal</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-xl font-semibold">
                {formatRupiah(ringkasan?.total_modal ?? 0)}
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle className="text-sm font-medium">Total Margin</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-xl font-semibold">
                {formatRupiah(ringkasan?.total_margin ?? 0)}
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle className="text-sm font-medium">Rata-rata Margin/Kategori</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-xl font-semibold">
                {formatRupiah(ringkasan?.rata_rata_margin_per_kategori ?? 0)}
              </div>
            </CardContent>
          </Card>
        </div>

        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle className="text-sm font-medium">Kategori Margin Tertinggi</CardTitle>
            </CardHeader>
            <CardContent>
              {ringkasan?.kategori_margin_tertinggi ? (
                <div className="space-y-1 text-sm">
                  <div className="font-semibold">
                    {ringkasan.kategori_margin_tertinggi.kategori}
                  </div>
                  <div className="text-muted-foreground">
                    Margin: {formatRupiah(ringkasan.kategori_margin_tertinggi.margin)}
                  </div>
                  <div className="text-muted-foreground">
                    Margin %: {ringkasan.kategori_margin_tertinggi.margin_persen}%
                  </div>
                </div>
              ) : (
                <div className="text-sm text-muted-foreground">Belum ada data.</div>
              )}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle className="text-sm font-medium">Kategori Margin Terendah</CardTitle>
            </CardHeader>
            <CardContent>
              {ringkasan?.kategori_margin_terendah ? (
                <div className="space-y-1 text-sm">
                  <div className="font-semibold">
                    {ringkasan.kategori_margin_terendah.kategori}
                  </div>
                  <div className="text-muted-foreground">
                    Margin: {formatRupiah(ringkasan.kategori_margin_terendah.margin)}
                  </div>
                  <div className="text-muted-foreground">
                    Margin %: {ringkasan.kategori_margin_terendah.margin_persen}%
                  </div>
                </div>
              ) : (
                <div className="text-sm text-muted-foreground">Belum ada data.</div>
              )}
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-medium">
              Perbandingan Pendapatan Kotor vs Modal per Kategori
            </CardTitle>
          </CardHeader>
          <CardContent>
            {rows.length === 0 && (
              <div className="text-sm text-muted-foreground">
                Belum ada data untuk filter yang dipilih.
              </div>
            )}
            {rows.length > 0 && (
              <div className="space-y-3">
                {rows.map((row: any) => {
                  const kotorWidth =
                    maxValue > 0 ? (row.pendapatan_kotor / maxValue) * 100 : 0;
                  const modalWidth =
                    maxValue > 0 ? (row.total_modal / maxValue) * 100 : 0;
                  return (
                    <div key={row.kategori_id} className="space-y-1">
                      <div className="flex items-center justify-between text-xs">
                        <div className="font-medium">{row.kategori}</div>
                        <div className="flex gap-3 text-muted-foreground">
                          <span>Kotor: {formatRupiah(row.pendapatan_kotor)}</span>
                          <span>Modal: {formatRupiah(row.total_modal)}</span>
                        </div>
                      </div>
                      <div className="flex h-4 items-center gap-1 rounded-md bg-muted px-1">
                        <Tooltip>
                          <TooltipTrigger className="h-2 rounded bg-sky-500" style={{ width: `${kotorWidth}%` }} />
                          <TooltipContent side="top">
                            <div className="space-y-1">
                              <div className="text-xs font-semibold">
                                {row.kategori} – Pendapatan Kotor
                              </div>
                              <div className="text-xs">
                                {formatRupiah(row.pendapatan_kotor)}
                              </div>
                            </div>
                          </TooltipContent>
                        </Tooltip>
                        <Tooltip>
                          <TooltipTrigger className="h-2 rounded bg-slate-500" style={{ width: `${modalWidth}%` }} />
                          <TooltipContent side="top">
                            <div className="space-y-1">
                              <div className="text-xs font-semibold">
                                {row.kategori} – Modal
                              </div>
                              <div className="text-xs">
                                {formatRupiah(row.total_modal)}
                              </div>
                            </div>
                          </TooltipContent>
                        </Tooltip>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-medium">
              Pendapatan Kotor vs Pendapatan Bersih per Kategori
            </CardTitle>
          </CardHeader>
          <CardContent>
            {rows.length === 0 && (
              <div className="text-sm text-muted-foreground">
                Belum ada data untuk filter yang dipilih.
              </div>
            )}
            {rows.length > 0 && (
              <div className="space-y-3">
                {rows.map((row: any) => {
                  const kotorWidth =
                    maxValue > 0 ? (row.pendapatan_kotor / maxValue) * 100 : 0;
                  const bersihWidth =
                    maxValue > 0 ? (row.pendapatan_bersih / maxValue) * 100 : 0;
                  return (
                    <div key={row.kategori_id} className="space-y-1">
                      <div className="flex items-center justify-between text-xs">
                        <div className="font-medium">{row.kategori}</div>
                        <div className="flex gap-3 text-muted-foreground">
                          <span>Kotor: {formatRupiah(row.pendapatan_kotor)}</span>
                          <span>Bersih: {formatRupiah(row.pendapatan_bersih)}</span>
                        </div>
                      </div>
                      <div className="flex h-4 items-center gap-1 rounded-md bg-muted px-1">
                        <Tooltip>
                          <TooltipTrigger className="h-2 rounded bg-sky-500" style={{ width: `${kotorWidth}%` }} />
                          <TooltipContent side="top">
                            <div className="space-y-1">
                              <div className="text-xs font-semibold">
                                {row.kategori} – Pendapatan Kotor
                              </div>
                              <div className="text-xs">
                                {formatRupiah(row.pendapatan_kotor)}
                              </div>
                            </div>
                          </TooltipContent>
                        </Tooltip>
                        <Tooltip>
                          <TooltipTrigger className="h-2 rounded bg-emerald-500" style={{ width: `${bersihWidth}%` }} />
                          <TooltipContent side="top">
                            <div className="space-y-1">
                              <div className="text-xs font-semibold">
                                {row.kategori} – Pendapatan Bersih
                              </div>
                              <div className="text-xs">
                                {formatRupiah(row.pendapatan_bersih)}
                              </div>
                            </div>
                          </TooltipContent>
                        </Tooltip>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}

