import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import {
    Building2, Users, CalendarClock, Phone, MapPin, Hash,
    CheckCircle2, XCircle, Package, Pencil, Trash2,
} from 'lucide-react';

interface Cabang {
    id: number;
    kode: string;
    nama: string;
    alamat: string | null;
    telepon: string | null;
    status: boolean;
}

interface StokInfo {
    id: number;
    jumlah: string | number;
    stok_minimum: string | number;
    tipe_stok: string;
    produk: {
        id: number;
        nama: string;
        sku: string;
    } | null;
}

interface UserInfo {
    id: number;
    name: string;
    email: string;
    role: string;
    aktif: boolean;
}

interface Props {
    cabang: Cabang;
    users: UserInfo[];
    users_count: number;
    shift_count: number;
    stok_info: StokInfo[];
}

function formatJumlah(val: string | number): string {
    const num = typeof val === 'string' ? parseFloat(val) : val;
    if (!Number.isFinite(num)) return '—';
    return num.toLocaleString('id-ID', { maximumFractionDigits: 2 });
}

const ROLE_CONFIG: Record<string, { label: string; color: string; dot: string }> = {
    manager:    { label: 'Manager',    color: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-400',     dot: 'bg-blue-500' },
    supervisor: { label: 'Supervisor', color: 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-400', dot: 'bg-purple-500' },
    kasir:      { label: 'Kasir',      color: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',  dot: 'bg-slate-400' },
};

export default function AdminCabangShow({ cabang, users, users_count, shift_count, stok_info }: Props) {
    const [deleting, setDeleting] = useState(false);

    const stokRendah = stok_info.filter(
        (s) => parseFloat(String(s.jumlah)) <= parseFloat(String(s.stok_minimum))
    );
    const canDelete = users_count === 0 && shift_count === 0 && stok_info.length === 0;

    function handleDelete() {
        if (deleting) {
            router.delete(admin.cabang.destroy(cabang.id).url);
        } else {
            setDeleting(true);
        }
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Cabang', href: admin.cabang.index().url },
                { title: cabang.nama, href: admin.cabang.show(cabang.id).url },
            ]}
        >
            <Head title={`Admin - ${cabang.nama}`} />
            <div className="space-y-6">

                {/* ── Header ── */}
                <div className="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-xl font-semibold">{cabang.nama}</h1>
                            <span
                                className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ${
                                    cabang.status
                                        ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-400'
                                        : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                                }`}
                            >
                                {cabang.status ? 'Aktif' : 'Nonaktif'}
                            </span>
                        </div>
                        <p className="mt-0.5 font-mono text-sm text-muted-foreground">{cabang.kode}</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={admin.cabang.edit(cabang.id).url}>
                                <Pencil className="mr-1.5 h-3.5 w-3.5" />
                                Edit
                            </Link>
                        </Button>
                        {deleting ? (
                            <>
                                <Button variant="destructive" size="sm" onClick={handleDelete}>
                                    Konfirmasi Hapus
                                </Button>
                                <Button variant="outline" size="sm" onClick={() => setDeleting(false)}>
                                    Batal
                                </Button>
                            </>
                        ) : (
                            <Button
                                variant="outline"
                                size="sm"
                                className="text-destructive hover:bg-destructive hover:text-destructive-foreground disabled:opacity-40"
                                onClick={handleDelete}
                                disabled={!canDelete}
                                title={!canDelete ? 'Hapus data terkait terlebih dahulu' : undefined}
                            >
                                <Trash2 className="mr-1.5 h-3.5 w-3.5" />
                                Hapus
                            </Button>
                        )}
                    </div>
                </div>

                {/* ── Stat Cards ── */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardContent className="flex items-center gap-3 pt-5">
                            <div className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full ${cabang.status ? 'bg-green-100 dark:bg-green-950' : 'bg-slate-100 dark:bg-slate-800'}`}>
                                {cabang.status
                                    ? <CheckCircle2 className="h-4 w-4 text-green-600 dark:text-green-400" />
                                    : <XCircle className="h-4 w-4 text-slate-500 dark:text-slate-400" />}
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">Status</p>
                                <p className="text-sm font-bold">{cabang.status ? 'Aktif' : 'Nonaktif'}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-3 pt-5">
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-950">
                                <Users className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">User Terdaftar</p>
                                <p className="text-lg font-bold">{users_count}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-3 pt-5">
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-950">
                                <CalendarClock className="h-4 w-4 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">Total Shift</p>
                                <p className="text-lg font-bold">{shift_count}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-3 pt-5">
                            <div className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full ${stokRendah.length > 0 ? 'bg-amber-100 dark:bg-amber-950' : 'bg-slate-100 dark:bg-slate-800'}`}>
                                <Package className={`h-4 w-4 ${stokRendah.length > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400'}`} />
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">Stok Etalase</p>
                                <p className="text-lg font-bold">{stok_info.length}</p>
                                {stokRendah.length > 0 && (
                                    <p className="text-[10px] text-amber-600 dark:text-amber-400">
                                        {stokRendah.length} item stok rendah
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">

                    {/* ── Info Card ── */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-950">
                                    <Building2 className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                </div>
                                <CardTitle className="text-sm font-medium">Informasi Cabang</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-start gap-3">
                                <Hash className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <p className="text-[11px] text-muted-foreground">Kode</p>
                                    <p className="font-mono text-sm font-medium">{cabang.kode}</p>
                                </div>
                            </div>
                            <div className="flex items-start gap-3">
                                <Building2 className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <p className="text-[11px] text-muted-foreground">Nama</p>
                                    <p className="text-sm font-medium">{cabang.nama}</p>
                                </div>
                            </div>
                            <div className="flex items-start gap-3">
                                <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <p className="text-[11px] text-muted-foreground">Alamat</p>
                                    {cabang.alamat
                                        ? <p className="text-sm">{cabang.alamat}</p>
                                        : <p className="text-sm text-muted-foreground">Belum diisi</p>
                                    }
                                </div>
                            </div>
                            <div className="flex items-start gap-3">
                                <Phone className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <p className="text-[11px] text-muted-foreground">Telepon</p>
                                    {cabang.telepon
                                        ? <p className="text-sm">{cabang.telepon}</p>
                                        : <p className="text-sm text-muted-foreground">Belum diisi</p>
                                    }
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* ── Users Card ── */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-950">
                                    <Users className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                </div>
                                <CardTitle className="text-sm font-medium">Daftar Pengguna</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {users.length === 0 ? (
                                <p className="text-sm text-muted-foreground">Belum ada pengguna di cabang ini.</p>
                            ) : (
                                <div className="space-y-1">
                                    {(['manager', 'supervisor', 'kasir'] as const)
                                        .map((role) => {
                                            const cfg = ROLE_CONFIG[role];
                                            const group = users.filter((u) => u.role === role);
                                            if (group.length === 0) return null;
                                            return (
                                                <div key={role}>
                                                    <p className="mb-1.5 mt-3 first:mt-0 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                                                        {cfg.label} ({group.length})
                                                    </p>
                                                    <div className="space-y-1">
                                                        {group.map((u) => (
                                                            <div
                                                                key={u.id}
                                                                className="flex items-center justify-between rounded-md px-2.5 py-2 hover:bg-muted/40 transition-colors"
                                                            >
                                                                <div className="flex items-center gap-2.5 min-w-0">
                                                                    <span className={`inline-block h-2 w-2 shrink-0 rounded-full ${u.aktif ? cfg.dot : 'bg-slate-300 dark:bg-slate-600'}`} />
                                                                    <span className="truncate text-sm font-medium">{u.name}</span>
                                                                </div>
                                                                <span
                                                                    className={`ml-2 shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ${
                                                                        u.aktif
                                                                            ? cfg.color
                                                                            : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'
                                                                    }`}
                                                                >
                                                                    {u.aktif ? cfg.label : 'Nonaktif'}
                                                                </span>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            );
                                        })}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* ── Stok Etalase Table ── */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                                    <Package className="h-4 w-4 text-slate-600 dark:text-slate-400" />
                                </div>
                                <CardTitle className="text-sm font-medium">Stok Etalase</CardTitle>
                            </div>
                            {stokRendah.length > 0 && (
                                <span className="text-xs font-medium text-amber-600 dark:text-amber-400">
                                    {stokRendah.length} item stok rendah
                                </span>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        {stok_info.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Belum ada data stok untuk cabang ini.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full text-xs">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="py-2 pr-4 text-left font-medium text-muted-foreground">Produk</th>
                                            <th className="py-2 pr-4 text-left font-medium text-muted-foreground">SKU</th>
                                            <th className="py-2 pr-4 text-left font-medium text-muted-foreground">Tipe</th>
                                            <th className="py-2 pr-4 text-right font-medium text-muted-foreground">Jumlah</th>
                                            <th className="py-2 pr-4 text-right font-medium text-muted-foreground">Minimum</th>
                                            <th className="py-2 text-left font-medium text-muted-foreground">Kondisi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {stok_info.map((stok) => {
                                            const rendah =
                                                parseFloat(String(stok.jumlah)) <= parseFloat(String(stok.stok_minimum));
                                            return (
                                                <tr
                                                    key={stok.id}
                                                    className="border-b last:border-0 transition-colors hover:bg-muted/30"
                                                >
                                                    <td className="py-2.5 pr-4 align-middle font-medium">
                                                        {stok.produk?.nama ?? '—'}
                                                    </td>
                                                    <td className="py-2.5 pr-4 align-middle font-mono text-muted-foreground">
                                                        {stok.produk?.sku ?? '—'}
                                                    </td>
                                                    <td className="py-2.5 pr-4 align-middle capitalize text-muted-foreground">
                                                        {stok.tipe_stok}
                                                    </td>
                                                    <td className="py-2.5 pr-4 text-right align-middle font-medium tabular-nums">
                                                        {formatJumlah(stok.jumlah)}
                                                    </td>
                                                    <td className="py-2.5 pr-4 text-right align-middle tabular-nums text-muted-foreground">
                                                        {formatJumlah(stok.stok_minimum)}
                                                    </td>
                                                    <td className="py-2.5 align-middle">
                                                        <span
                                                            className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ${
                                                                rendah
                                                                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400'
                                                                    : 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-400'
                                                            }`}
                                                        >
                                                            {rendah ? 'Stok Rendah' : 'Normal'}
                                                        </span>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                    <tfoot>
                                        <tr className="border-t bg-muted/30">
                                            <td colSpan={3} className="py-2.5 pr-4 text-xs font-semibold">
                                                Total
                                            </td>
                                            <td colSpan={3} className="py-2.5 text-xs text-muted-foreground">
                                                {stok_info.length} item &middot; {stokRendah.length} stok rendah
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

            </div>
        </AppLayout>
    );
}
