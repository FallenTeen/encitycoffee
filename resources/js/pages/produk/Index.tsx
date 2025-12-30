import InputError from '@/components/input-error';
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
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import ProdukCard from '@/components/produk/ProdukCard';
import { Grid3X3, List } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

interface KategoriOption {
    id: number;
    nama: string;
}

interface ProdukItem {
    id: number;
    sku: string;
    nama: string;
    kelompok_nama?: string | null;
    varian?: string | null;
    tipe: string;
    harga_jual: number | string;
    aktif: boolean;
    image_path?: string | null;
    kategori?: { id: number; nama: string };
}

interface ProdukPaginator {
    data: ProdukItem[];
    total: number;
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface FilterAktif {
    kategori_id?: number | string | null;
    tipe?: string | null;
    aktif?: boolean | string | null;
    pencarian?: string | null;
}

interface Props {
    produks: ProdukPaginator;
    kategori_list: KategoriOption[];
    filter_aktif: FilterAktif;
    cabangList?: Array<{id: number, nama: string, kode: string}>;
    selectedCabang?: {id: number, nama: string, kode: string} | null;
    cacheInfo?: {has_cache: boolean, cache_key: string, ttl: number};
    canManageProduk?: boolean;
}

export default function ProdukIndex({
    produks,
    kategori_list,
    filter_aktif,
    cabangList,
    selectedCabang,
    cacheInfo,
    canManageProduk = false,
}: Props) {
    const [viewMode, setViewMode] = useState<'table' | 'card'>('table');
    const aktifValue = filter_aktif?.aktif;
    const { data, setData, get, processing, errors } = useForm({
        pencarian: filter_aktif?.pencarian ?? '',
        kategori_id: filter_aktif?.kategori_id
            ? String(filter_aktif.kategori_id)
            : '',
        tipe: filter_aktif?.tipe ?? '',
        aktif:
            aktifValue === null || aktifValue === undefined
                ? ''
                : typeof aktifValue === 'string'
                  ? aktifValue
                  : aktifValue
                    ? '1'
                    : '0',
    });

    const kategoriOptions = useMemo(
        () => [{ id: 0, nama: 'Semua kategori' }].concat(kategori_list ?? []),
        [kategori_list],
    );

    const submit = () => {
        get('/produk', {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const formatHarga = (value: number | string) => {
        const num = typeof value === 'string' ? Number(value) : value;
        if (Number.isNaN(num)) return '-';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(num);
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Produk', href: '/produk' }]}>
            <Head title="Produk" />
            <div className="space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Daftar Produk</h1>
                        <div className="text-sm text-muted-foreground">
                            Total: {produks?.total ?? 0}
                        </div>
                    </div>
                    <Button asChild>
                        <Link href="/produk/create">Tambah Produk</Link>
                    </Button>
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
                            <Label htmlFor="pencarian">Cari</Label>
                            <Input
                                id="pencarian"
                                value={data.pencarian}
                                onChange={(e) =>
                                    setData('pencarian', e.target.value)
                                }
                                placeholder="Nama atau SKU"
                            />
                            <InputError message={errors.pencarian as string} />
                        </div>

                        <div className="space-y-1">
                            <Label>Kategori</Label>
                            <Select
                                value={data.kategori_id}
                                onValueChange={(value) =>
                                    setData(
                                        'kategori_id',
                                        value === '0' ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Semua kategori" />
                                </SelectTrigger>
                                <SelectContent>
                                    {kategoriOptions.map((k) => (
                                        <SelectItem
                                            key={k.id}
                                            value={String(k.id)}
                                        >
                                            {k.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError
                                message={errors.kategori_id as string}
                            />
                        </div>

                        <div className="space-y-1">
                            <Label>Tipe</Label>
                            <Select
                                value={data.tipe}
                                onValueChange={(value) =>
                                    setData(
                                        'tipe',
                                        value === '__all__' ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Semua tipe" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__all__">
                                        Semua tipe
                                    </SelectItem>
                                    <SelectItem value="beans">Beans</SelectItem>
                                    <SelectItem value="minuman">
                                        Minuman
                                    </SelectItem>
                                    <SelectItem value="snack">Snack</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.tipe as string} />
                        </div>

                        <div className="space-y-1">
                            <Label>Status</Label>
                            <Select
                                value={data.aktif}
                                onValueChange={(value) =>
                                    setData(
                                        'aktif',
                                        value === '__all__' ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Semua status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__all__">
                                        Semua status
                                    </SelectItem>
                                    <SelectItem value="1">Aktif</SelectItem>
                                    <SelectItem value="0">Nonaktif</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.aktif as string} />
                        </div>

                        <div className="flex items-center gap-2 pt-2 md:col-span-4">
                            <Button type="submit" disabled={processing}>
                                Terapkan Filter
                            </Button>
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => {
                                    setData({
                                        pencarian: '',
                                        kategori_id: '',
                                        tipe: '',
                                        aktif: '',
                                    });
                                    router.get(
                                        '/produk',
                                        {},
                                        { preserveScroll: true, replace: true },
                                    );
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
                                    <th className="px-4 py-2">Gambar</th>
                                    <th className="px-4 py-2">SKU</th>
                                    <th className="px-4 py-2">Nama</th>
                                    <th className="px-4 py-2">Kategori</th>
                                    <th className="px-4 py-2">Tipe</th>
                                    <th className="px-4 py-2">Harga Jual</th>
                                    <th className="px-4 py-2">Status</th>
                                    <th className="px-4 py-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {(produks?.data ?? []).map((p) => (
                                    <tr
                                        key={p.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-4 py-2">
                                            {p.image_path ? (
                                                <img
                                                    src={`/storage/${p.image_path}`}
                                                    alt={p.nama}
                                                    className="h-12 w-12 rounded object-cover"
                                                />
                                            ) : (
                                                <div className="flex h-12 w-12 items-center justify-center rounded bg-muted text-xs text-muted-foreground">
                                                    No img
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-4 py-2 font-mono text-xs">
                                            {p.sku}
                                        </td>
                                        <td className="px-4 py-2 font-medium">
                                            {p.varian
                                                ? `${p.varian} ${p.kelompok_nama || p.nama}`
                                                : p.nama}
                                        </td>
                                        <td className="px-4 py-2">
                                            {p.kategori?.nama ?? '-'}
                                        </td>
                                        <td className="px-4 py-2 capitalize">
                                            {p.tipe}
                                        </td>
                                        <td className="px-4 py-2">
                                            {formatHarga(p.harga_jual)}
                                        </td>
                                        <td className="px-4 py-2">
                                            <span className="inline-flex items-center rounded bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                                                {p.aktif ? 'Aktif' : 'Nonaktif'}
                                            </span>
                                        </td>
                                        <td className="px-4 py-2">
                                            <div className="flex items-center gap-3">
                                                <Link
                                                    href={`/produk/${p.id}`}
                                                    className="text-primary underline"
                                                >
                                                    Detail
                                                </Link>
                                                <Link
                                                    href={`/produk/${p.id}/edit`}
                                                    className="text-primary underline"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    type="button"
                                                    className="text-destructive underline"
                                                    onClick={() => {
                                                        const ok =
                                                            window.confirm(
                                                                `Hapus produk ${p.nama}?`,
                                                            );
                                                        if (!ok) return;
                                                        router.delete(
                                                            `/produk/${p.id}`,
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        );
                                                    }}
                                                >
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {(produks?.data ?? []).length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            Belum ada data produk.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="flex items-center justify-between border-t p-4 text-sm">
                        <div>
                            Halaman {produks?.current_page ?? 1} /{' '}
                            {produks?.last_page ?? 1}
                        </div>
                        <div className="flex gap-2">
                            <Button
                                asChild
                                variant="secondary"
                                disabled={!produks?.prev_page_url}
                            >
                                <Link
                                    href={produks?.prev_page_url ?? '/produk'}
                                >
                                    Sebelumnya
                                </Link>
                            </Button>
                            <Button
                                asChild
                                variant="secondary"
                                disabled={!produks?.next_page_url}
                            >
                                <Link
                                    href={produks?.next_page_url ?? '/produk'}
                                >
                                    Berikutnya
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
