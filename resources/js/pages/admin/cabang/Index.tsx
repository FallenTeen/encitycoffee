import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import admin from '@/routes/admin';
import {
    Building2, Users, CalendarClock, Search, Plus,
    Eye, Pencil, Trash2, CheckCircle2, XCircle,
} from 'lucide-react';

interface Cabang {
    id: number;
    kode: string;
    nama: string;
    alamat: string | null;
    telepon: string | null;
    status: boolean;
    users_count: number;
    shift_count: number;
}

interface PaginatedCabang {
    data: Cabang[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Props {
    cabangs: PaginatedCabang;
}

export default function AdminCabangIndex({ cabangs }: Props) {
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState<'semua' | 'aktif' | 'nonaktif'>('semua');
    const [deletingId, setDeletingId] = useState<number | null>(null);

    const allData = cabangs?.data ?? [];

    const filtered = useMemo(() => {
        return allData.filter((c) => {
            const q = search.toLowerCase();
            const matchSearch =
                q === '' ||
                c.nama.toLowerCase().includes(q) ||
                c.kode.toLowerCase().includes(q) ||
                (c.alamat ?? '').toLowerCase().includes(q) ||
                (c.telepon ?? '').includes(q);
            const matchStatus =
                statusFilter === 'semua' ||
                (statusFilter === 'aktif' && c.status) ||
                (statusFilter === 'nonaktif' && !c.status);
            return matchSearch && matchStatus;
        });
    }, [allData, search, statusFilter]);

    const totalAktif = allData.filter((c) => c.status).length;
    const totalNonaktif = allData.filter((c) => !c.status).length;

    function handleDelete(cabang: Cabang) {
        if (deletingId === cabang.id) {
            router.delete(admin.cabang.destroy(cabang.id).url, {
                onFinish: () => setDeletingId(null),
            });
        } else {
            setDeletingId(cabang.id);
        }
    }

    return (
        <AppLayout breadcrumbs={[{ title: 'Cabang', href: admin.cabang.index() }]}>
            <Head title="Admin - Manajemen Cabang" />
            <div className="space-y-6">

                {/* ── Header ── */}
                <div className="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Manajemen Cabang</h1>
                        <p className="text-sm text-muted-foreground">
                            Kelola data cabang, status operasional, dan informasi kontak.
                        </p>
                    </div>
                    <Button asChild size="sm" className="w-fit">
                        <Link href={admin.cabang.create()}>
                            <Plus className="mr-1.5 h-4 w-4" />
                            Tambah Cabang
                        </Link>
                    </Button>
                </div>

                {/* ── Stat Cards ── */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardContent className="flex items-center gap-3 pt-5">
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-950">
                                <Building2 className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">Total Cabang</p>
                                <p className="text-lg font-bold">{cabangs?.total ?? 0}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-3 pt-5">
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-950">
                                <CheckCircle2 className="h-4 w-4 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">Aktif</p>
                                <p className="text-lg font-bold">{totalAktif}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-3 pt-5">
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                                <XCircle className="h-4 w-4 text-slate-500 dark:text-slate-400" />
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">Nonaktif</p>
                                <p className="text-lg font-bold">{totalNonaktif}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* ── Table Card ── */}
                <Card>
                    <CardHeader>
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <CardTitle className="text-sm font-medium">Daftar Cabang</CardTitle>
                            <div className="flex flex-wrap items-center gap-2">
                                <div className="flex items-center gap-1 rounded-md border bg-muted/40 p-0.5 text-xs">
                                    {(['semua', 'aktif', 'nonaktif'] as const).map((s) => (
                                        <button
                                            key={s}
                                            onClick={() => setStatusFilter(s)}
                                            className={`rounded px-2.5 py-1 font-medium capitalize transition-colors ${
                                                statusFilter === s
                                                    ? 'bg-background text-foreground shadow-sm'
                                                    : 'text-muted-foreground hover:text-foreground'
                                            }`}
                                        >
                                            {s === 'semua' ? 'Semua' : s.charAt(0).toUpperCase() + s.slice(1)}
                                        </button>
                                    ))}
                                </div>
                                <div className="relative">
                                    <Search className="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        placeholder="Cari kode, nama, atau alamat..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="h-8 w-56 pl-8 text-xs"
                                    />
                                </div>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-xs">
                                <thead>
                                    <tr className="border-b">
                                        <th className="py-2 pr-4 text-left font-medium text-muted-foreground">Kode</th>
                                        <th className="py-2 pr-4 text-left font-medium text-muted-foreground">Nama</th>
                                        <th className="py-2 pr-4 text-left font-medium text-muted-foreground">Alamat</th>
                                        <th className="py-2 pr-4 text-left font-medium text-muted-foreground">Telepon</th>
                                        <th className="py-2 pr-4 text-center font-medium text-muted-foreground">
                                            <span className="flex items-center justify-center gap-1">
                                                <Users className="h-3 w-3" />
                                                User
                                            </span>
                                        </th>
                                        <th className="py-2 pr-4 text-center font-medium text-muted-foreground">
                                            <span className="flex items-center justify-center gap-1">
                                                <CalendarClock className="h-3 w-3" />
                                                Shift
                                            </span>
                                        </th>
                                        <th className="py-2 pr-4 text-left font-medium text-muted-foreground">Status</th>
                                        <th className="py-2 text-right font-medium text-muted-foreground">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {filtered.map((cabang) => (
                                        <tr
                                            key={cabang.id}
                                            className="border-b last:border-0 transition-colors hover:bg-muted/30"
                                        >
                                            <td className="py-2.5 pr-4 align-middle font-mono font-medium">
                                                {cabang.kode}
                                            </td>
                                            <td className="py-2.5 pr-4 align-middle font-medium">
                                                {cabang.nama}
                                            </td>
                                            <td className="max-w-[160px] py-2.5 pr-4 align-middle">
                                                <span className="block truncate text-muted-foreground">
                                                    {cabang.alamat || '—'}
                                                </span>
                                            </td>
                                            <td className="py-2.5 pr-4 align-middle text-muted-foreground">
                                                {cabang.telepon || '—'}
                                            </td>
                                            <td className="py-2.5 pr-4 text-center align-middle">
                                                {cabang.users_count ?? 0}
                                            </td>
                                            <td className="py-2.5 pr-4 text-center align-middle">
                                                {cabang.shift_count ?? 0}
                                            </td>
                                            <td className="py-2.5 pr-4 align-middle">
                                                <span
                                                    className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ${
                                                        cabang.status
                                                            ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-400'
                                                            : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                                                    }`}
                                                >
                                                    {cabang.status ? 'Aktif' : 'Nonaktif'}
                                                </span>
                                            </td>
                                            <td className="py-2.5 align-middle text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button variant="ghost" size="sm" asChild className="h-7 px-2">
                                                        <Link href={admin.cabang.show(cabang.id)}>
                                                            <Eye className="h-3.5 w-3.5" />
                                                        </Link>
                                                    </Button>
                                                    <Button variant="ghost" size="sm" asChild className="h-7 px-2">
                                                        <Link href={admin.cabang.edit(cabang.id)}>
                                                            <Pencil className="h-3.5 w-3.5" />
                                                        </Link>
                                                    </Button>
                                                    {deletingId === cabang.id ? (
                                                        <div className="flex items-center gap-1">
                                                            <button
                                                                onClick={() => handleDelete(cabang)}
                                                                className="rounded px-2 py-0.5 text-[10px] font-medium bg-destructive text-destructive-foreground hover:bg-destructive/90 transition-colors"
                                                            >
                                                                Konfirmasi
                                                            </button>
                                                            <button
                                                                onClick={() => setDeletingId(null)}
                                                                className="rounded px-2 py-0.5 text-[10px] font-medium text-muted-foreground hover:text-foreground transition-colors"
                                                            >
                                                                Batal
                                                            </button>
                                                        </div>
                                                    ) : (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="h-7 px-2 text-muted-foreground hover:text-destructive"
                                                            onClick={() => setDeletingId(cabang.id)}
                                                        >
                                                            <Trash2 className="h-3.5 w-3.5" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {filtered.length === 0 && (
                                        <tr>
                                            <td colSpan={8} className="py-10 text-center text-muted-foreground">
                                                {search || statusFilter !== 'semua'
                                                    ? 'Tidak ada cabang yang sesuai filter.'
                                                    : 'Belum ada data cabang.'}
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* ── Pagination ── */}
                        {(cabangs?.last_page ?? 1) > 1 && (
                            <div className="mt-4 flex items-center justify-between border-t pt-4">
                                <p className="text-xs text-muted-foreground">
                                    Menampilkan {cabangs.from ?? 0}–{cabangs.to ?? 0} dari {cabangs.total} cabang
                                </p>
                                <div className="flex gap-1">
                                    {cabangs.links.map((link, i) =>
                                        link.url ? (
                                            <button
                                                key={i}
                                                onClick={() => router.visit(link.url!)}
                                                className={`rounded px-2.5 py-1 text-xs transition-colors ${
                                                    link.active
                                                        ? 'bg-primary text-primary-foreground'
                                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                                }`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ) : (
                                            <span
                                                key={i}
                                                className="cursor-default rounded px-2.5 py-1 text-xs text-muted-foreground/40"
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        )
                                    )}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

            </div>
        </AppLayout>
    );
}
