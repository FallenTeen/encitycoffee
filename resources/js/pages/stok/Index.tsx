import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import laporan from '@/routes/laporan';
import stok from '@/routes/stok';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

type TipeStok = 'produksi_minuman' | 'penjualan_retail';

interface CabangOption {
    id: number;
    nama: string;
}

interface ProdukInfo {
    id: number;
    nama: string;
    sku?: string;
    kategori?: { id: number; nama: string } | null;
}

interface CabangInfo {
    id: number;
    nama?: string;
}

interface BatchInfo {
    id: number;
    jumlah: number;
    tanggal_kadaluarsa?: string | null;
}

interface StokItem {
    id: number;
    cabang_id: number;
    tipe_stok: TipeStok;
    jumlah: number;
    stok_minimum: number;
    produk?: ProdukInfo | null;
    cabang?: CabangInfo | null;
    batch?: BatchInfo[];
    nilai_total: number;
    is_stok_rendah: boolean;
    batch_kadaluarsa_count: number;
}

interface StatistikStok {
    total_item: number;
    item_stok_rendah: number;
    total_nilai_inventori: number;
    item_mendekati_kadaluarsa: number;
}

interface FilterAktif {
    cabang_id?: number | null;
    tipe_stok?: TipeStok | '';
    status?: 'semua' | 'rendah' | 'normal' | '';
}

interface Props {
    stoks: StokItem[];
    statistik: StatistikStok;
    filter_aktif: FilterAktif;
    cabang_list: CabangOption[];
}

type ViewMode = 'semua' | 'rendah' | 'normal' | 'kadaluarsa';

function formatRupiah(value: number) {
    if (Number.isNaN(value)) return '-';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

export default function StokIndex({
    stoks,
    statistik,
    filter_aktif,
    cabang_list,
}: Props) {
    const [view, setView] = useState<ViewMode>(() => {
        if (filter_aktif?.status === 'rendah') {
            return 'rendah';
        }
        if (filter_aktif?.status === 'normal') {
            return 'normal';
        }
        return 'semua';
    });
    const { data, setData, get, processing, errors } = useForm({
        cabang_id: filter_aktif?.cabang_id ? String(filter_aktif.cabang_id) : '',
        tipe_stok: filter_aktif?.tipe_stok ?? '',
        status: filter_aktif?.status ?? '',
    });

    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({
                only: ['stoks', 'statistik', 'filter_aktif'],
            });
        }, 30000);

        return () => clearInterval(interval);
    }, []);

    const submit = () => {
        get(stok.index().url, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const cabangOptions = useMemo(
        () => [{ id: 0, nama: 'Semua cabang' }].concat(cabang_list ?? []),
        [cabang_list],
    );

    const filteredStoks = useMemo(() => {
        if (view === 'kadaluarsa') {
            return (stoks ?? []).filter(
                (s) => (s.batch_kadaluarsa_count ?? 0) > 0,
            );
        }
        return stoks ?? [];
    }, [stoks, view]);

    return (
        <AppLayout breadcrumbs={[{ title: 'Stok', href: stok.index().url }]}>
            <Head title="Stok" />
            <div className="space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Ringkasan Stok</h1>
                        <div className="text-sm text-muted-foreground">
                            Monitoring persediaan, stok rendah, dan kadaluarsa secara terpadu.
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button
                            asChild
                            variant="outline"
                            size="sm"
                        >
                            <Link href={laporan.stok().url}>Laporan Stok</Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <CardDescription>Total Item</CardDescription>
                            <CardTitle className="text-2xl">
                                {statistik?.total_item ?? 0}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardDescription>Item Stok Rendah</CardDescription>
                            <CardTitle className="text-2xl">
                                {statistik?.item_stok_rendah ?? 0}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardDescription>Nilai Inventori</CardDescription>
                            <CardTitle className="text-2xl">
                                {formatRupiah(statistik?.total_nilai_inventori ?? 0)}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardDescription>Batch Mendekati Kadaluarsa</CardDescription>
                            <CardTitle className="text-2xl">
                                {statistik?.item_mendekati_kadaluarsa ?? 0}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Panel Navigasi Stok
                        </CardTitle>
                        <CardDescription>
                            Akses cepat ke tampilan stok utama dan kondisi kritis.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                size="sm"
                                variant={view === 'semua' ? 'default' : 'outline'}
                                onClick={() => {
                                    setView('semua');
                                    setData('status', '');
                                    submit();
                                }}
                            >
                                Semua Stok
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant={view === 'rendah' ? 'default' : 'outline'}
                                onClick={() => {
                                    setView('rendah');
                                    setData('status', 'rendah');
                                    submit();
                                }}
                            >
                                Stok Rendah
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant={view === 'normal' ? 'default' : 'outline'}
                                onClick={() => {
                                    setView('normal');
                                    setData('status', 'normal');
                                    submit();
                                }}
                            >
                                Stok Normal
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant={view === 'kadaluarsa' ? 'default' : 'outline'}
                                onClick={() => {
                                    setView('kadaluarsa');
                                }}
                            >
                                Mendekati Kadaluarsa
                                <Badge variant="destructive" className="ml-2">
                                    {statistik?.item_mendekati_kadaluarsa ?? 0}
                                </Badge>
                            </Button>
                        </div>

                        <form
                            className="grid grid-cols-1 gap-4 md:grid-cols-4"
                            onSubmit={(e) => {
                                e.preventDefault();
                                submit();
                            }}
                        >
                            <div className="space-y-1">
                                <div className="text-sm font-medium">Cabang</div>
                                <Select
                                    value={data.cabang_id}
                                    onValueChange={(value) =>
                                        setData('cabang_id', value === '0' ? '' : value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Semua cabang" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {cabangOptions.map((c) => (
                                            <SelectItem key={c.id} value={String(c.id)}>
                                                {c.nama}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.cabang_id as string} />
                            </div>

                            <div className="space-y-1">
                                <div className="text-sm font-medium">Tipe Stok</div>
                                <Select
                                    value={data.tipe_stok}
                                    onValueChange={(value) =>
                                        setData(
                                            'tipe_stok',
                                            value === '__all__' ? '' : (value as TipeStok),
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Semua tipe" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__all__">Semua tipe</SelectItem>
                                        <SelectItem value="produksi_minuman">
                                            Produksi minuman
                                        </SelectItem>
                                        <SelectItem value="penjualan_retail">
                                            Penjualan retail
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.tipe_stok as string} />
                            </div>

                            <div className="space-y-1">
                                <div className="text-sm font-medium">Status</div>
                                <Select
                                    value={data.status}
                                    onValueChange={(value) => {
                                        const next =
                                            value === '__all__'
                                                ? ''
                                                : (value as FilterAktif['status']);
                                        setData('status', next ?? '');
                                        if (next === 'rendah' || next === 'normal') {
                                            setView(next);
                                        } else {
                                            setView('semua');
                                        }
                                    }}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Semua status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__all__">Semua status</SelectItem>
                                        <SelectItem value="rendah">Stok rendah</SelectItem>
                                        <SelectItem value="normal">Stok normal</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status as string} />
                            </div>

                            <div className="flex items-end gap-2">
                                <Button type="submit" disabled={processing}>
                                    Terapkan
                                </Button>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => {
                                        setData({
                                            cabang_id: '',
                                            tipe_stok: '',
                                            status: '',
                                        });
                                        setView('semua');
                                        router.get(stok.index().url, {}, {
                                            preserveScroll: true,
                                            replace: true,
                                        });
                                    }}
                                >
                                    Reset
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Daftar Stok</CardTitle>
                        <CardDescription>
                            Detail stok per produk dan cabang berdasarkan filter aktif.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left">
                                        <th className="px-4 py-2">Produk</th>
                                        <th className="px-4 py-2">Cabang</th>
                                        <th className="px-4 py-2">Tipe</th>
                                        <th className="px-4 py-2 text-right">Jumlah</th>
                                        <th className="px-4 py-2 text-right">Minimum</th>
                                        <th className="px-4 py-2 text-right">
                                            Nilai Stok
                                        </th>
                                        <th className="px-4 py-2 text-center">
                                            Kadaluarsa
                                        </th>
                                        <th className="px-4 py-2 text-center">Status</th>
                                        <th className="px-4 py-2 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {filteredStoks.map((s) => {
                                        const isLow = s.is_stok_rendah;
                                        const hasExpiring =
                                            (s.batch_kadaluarsa_count ?? 0) > 0;
                                        return (
                                            <tr
                                                key={s.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="px-4 py-2">
                                                    <div className="font-medium">
                                                        {s.produk?.nama ?? '-'}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {s.produk?.sku ?? ''}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-2">
                                                    {s.cabang?.nama ?? '-'}
                                                </td>
                                                <td className="px-4 py-2 capitalize">
                                                    {s.tipe_stok === 'produksi_minuman'
                                                        ? 'Produksi minuman'
                                                        : 'Penjualan retail'}
                                                </td>
                                                <td className="px-4 py-2 text-right">
                                                    {s.jumlah}
                                                </td>
                                                <td className="px-4 py-2 text-right">
                                                    {s.stok_minimum}
                                                </td>
                                                <td className="px-4 py-2 text-right">
                                                    {formatRupiah(s.nilai_total)}
                                                </td>
                                                <td className="px-4 py-2 text-center">
                                                    {hasExpiring ? (
                                                        <Badge variant="destructive">
                                                            {s.batch_kadaluarsa_count}{' '}
                                                            batch
                                                        </Badge>
                                                    ) : (
                                                        <span className="text-xs text-muted-foreground">
                                                            -
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-2 text-center">
                                                    <Badge
                                                        variant={
                                                            isLow
                                                                ? 'destructive'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {isLow
                                                            ? 'Rendah'
                                                            : 'Normal'}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-2 text-center">
                                                    <div className="flex items-center justify-center gap-2">
                                                        <Button
                                                            asChild
                                                            size="sm"
                                                            variant="outline"
                                                        >
                                                            <Link
                                                                href={stok.mutasi(
                                                                    s.id,
                                                                ).url}
                                                            >
                                                                Riwayat
                                                            </Link>
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                    {filteredStoks.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={9}
                                                className="px-4 py-8 text-center text-muted-foreground"
                                            >
                                                Belum ada data stok untuk filter saat
                                                ini.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
