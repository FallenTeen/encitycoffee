import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import admin from '@/routes/admin';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useMemo } from 'react';
import Swal from 'sweetalert2';
import {
    Users, Search, RotateCcw, UserPlus, Pencil, Trash2,
    ChevronLeft, ChevronRight, Building2, ShieldCheck, BadgeCheck,
} from 'lucide-react';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    aktif: boolean;
    cabang?: Array<{ id: number; kode?: string; nama?: string }>;
    created_at?: string;
}

interface Props {
    users: {
        data: User[];
        total: number;
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    cabangs: Array<{ id: number; kode: string; nama: string | null }>;
    filter_aktif: {
        search: string;
        role: string;
        aktif: string;
        cabang_id: string | number;
        per_page: number;
    };
}

const ROLE_CONFIG: Record<string, { label: string; color: string; icon: React.ReactNode }> = {
    it_support: {
        label: 'IT Support',
        color: 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-400',
        icon: <ShieldCheck className="h-3 w-3" />,
    },
    manager: {
        label: 'Manager',
        color: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-400',
        icon: <BadgeCheck className="h-3 w-3" />,
    },
    supervisor: {
        label: 'Supervisor',
        color: 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-400',
        icon: <BadgeCheck className="h-3 w-3" />,
    },
    kasir: {
        label: 'Kasir',
        color: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        icon: <BadgeCheck className="h-3 w-3" />,
    },
};

export default function AdminUsersIndex({ users, cabangs, filter_aktif }: Props) {
    const { data, setData, get, processing, errors } = useForm({
        search: filter_aktif?.search ?? '',
        role: filter_aktif?.role ?? '',
        aktif: filter_aktif?.aktif ?? '',
        cabang_id: filter_aktif?.cabang_id ? String(filter_aktif.cabang_id) : '',
        per_page: String(filter_aktif?.per_page ?? 15),
    });

    const cabangOptions = useMemo(
        () =>
            [{ id: 0, kode: '-', nama: 'Semua cabang' }].concat(
                (cabangs ?? []).map((c) => ({ id: c.id, kode: c.kode, nama: c.nama ?? '-' })),
            ),
        [cabangs],
    );

    function submit() {
        get(admin.users.index().url, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    function reset() {
        setData({ search: '', role: '', aktif: '', cabang_id: '', per_page: '15' });
        router.get(admin.users.index().url, {}, { preserveScroll: true, replace: true });
    }

    async function handleDelete(user: User) {
        const result = await Swal.fire({
            title: 'Hapus Pengguna',
            text: `Apakah Anda yakin ingin menghapus "${user.name}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            focusCancel: true,
        });
        if (!result.isConfirmed) return;
        router.delete(admin.users.destroy(user.id).url, { preserveScroll: true });
    }

    const hasFilter = data.search || data.role || data.aktif || data.cabang_id;

    return (
        <AppLayout breadcrumbs={[{ title: 'Pengguna', href: admin.users.index() }]}>
            <Head title="Admin - Users" />
            <div className="space-y-6">

                {/* ── Header ── */}
                <div className="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Pengelolaan Pengguna</h1>
                        <p className="text-sm text-muted-foreground">
                            Total{' '}
                            <span className="font-medium text-foreground">{users?.total ?? 0}</span>{' '}
                            pengguna terdaftar
                        </p>
                    </div>
                    <Button size="sm" asChild>
                        <Link href={admin.users.create()}>
                            <UserPlus className="mr-1.5 h-3.5 w-3.5" />
                            Tambah Pengguna
                        </Link>
                    </Button>
                </div>

                {/* ── Filter Card ── */}
                <Card>
                    <CardHeader className="pb-3">
                        <div className="flex items-center gap-2">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                                <Search className="h-4 w-4 text-slate-600 dark:text-slate-400" />
                            </div>
                            <CardTitle className="text-sm font-medium">Filter & Pencarian</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={(e) => { e.preventDefault(); submit(); }}
                            className="space-y-4"
                        >
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                {/* Search */}
                                <div className="space-y-1.5 lg:col-span-2">
                                    <Label htmlFor="search" className="text-xs font-medium">Cari Pengguna</Label>
                                    <div className="relative">
                                        <Search className="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                                        <Input
                                            id="search"
                                            value={data.search}
                                            onChange={(e) => setData('search', e.target.value)}
                                            placeholder="Nama atau email..."
                                            className="pl-8"
                                        />
                                    </div>
                                    <InputError message={errors.search} />
                                </div>

                                {/* Role */}
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-medium">Role</Label>
                                    <Select value={data.role || '__all__'} onValueChange={(v) => setData('role', v === '__all__' ? '' : v)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Semua role" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__all__">Semua role</SelectItem>
                                            <SelectItem value="it_support">IT Support</SelectItem>
                                            <SelectItem value="manager">Manager</SelectItem>
                                            <SelectItem value="supervisor">Supervisor</SelectItem>
                                            <SelectItem value="kasir">Kasir</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.role} />
                                </div>

                                {/* Status */}
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-medium">Status</Label>
                                    <Select value={data.aktif || '__all__'} onValueChange={(v) => setData('aktif', v === '__all__' ? '' : v)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Semua status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__all__">Semua status</SelectItem>
                                            <SelectItem value="1">Aktif</SelectItem>
                                            <SelectItem value="0">Nonaktif</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.aktif} />
                                </div>

                                {/* Cabang */}
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-medium">Cabang</Label>
                                    <Select value={data.cabang_id || '0'} onValueChange={(v) => setData('cabang_id', v === '0' ? '' : v)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Semua cabang" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {cabangOptions.map((c) => (
                                                <SelectItem key={c.id} value={String(c.id)}>
                                                    {c.id === 0 ? 'Semua cabang' : `${c.kode} — ${c.nama}`}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.cabang_id} />
                                </div>

                                {/* Per page */}
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-medium">Tampil per halaman</Label>
                                    <Select value={data.per_page} onValueChange={(v) => setData('per_page', v)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="15">15</SelectItem>
                                            <SelectItem value="25">25</SelectItem>
                                            <SelectItem value="50">50</SelectItem>
                                            <SelectItem value="100">100</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="flex items-center gap-2 border-t pt-3">
                                <Button type="submit" size="sm" disabled={processing}>
                                    <Search className="mr-1.5 h-3.5 w-3.5" />
                                    {processing ? 'Mencari...' : 'Terapkan Filter'}
                                </Button>
                                {hasFilter && (
                                    <Button type="button" variant="outline" size="sm" onClick={reset}>
                                        <RotateCcw className="mr-1.5 h-3.5 w-3.5" />
                                        Reset
                                    </Button>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* ── Table Card ── */}
                <Card>
                    <CardHeader className="pb-3">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-950">
                                    <Users className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                </div>
                                <CardTitle className="text-sm font-medium">Daftar Pengguna</CardTitle>
                            </div>
                            <span className="text-xs text-muted-foreground">
                                {users?.data?.length ?? 0} dari {users?.total ?? 0} pengguna
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/30">
                                        <th className="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground">Pengguna</th>
                                        <th className="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground">Role</th>
                                        <th className="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground">Cabang</th>
                                        <th className="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground">Status</th>
                                        <th className="px-4 py-2.5 text-right text-xs font-medium text-muted-foreground">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(users?.data ?? []).map((u) => {
                                        const roleCfg = ROLE_CONFIG[u.role] ?? { label: u.role, color: 'bg-slate-100 text-slate-700', icon: null };
                                        const cabangs = u.cabang ?? [];
                                        return (
                                            <tr key={u.id} className="border-b last:border-0 transition-colors hover:bg-muted/20">
                                                {/* Pengguna */}
                                                <td className="px-4 py-3 align-middle">
                                                    <div className="flex items-center gap-2.5">
                                                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-semibold uppercase text-muted-foreground">
                                                            {u.name.charAt(0)}
                                                        </div>
                                                        <div>
                                                            <p className="font-medium leading-tight">{u.name}</p>
                                                            <p className="text-[11px] text-muted-foreground">{u.email}</p>
                                                        </div>
                                                    </div>
                                                </td>

                                                {/* Role */}
                                                <td className="px-4 py-3 align-middle">
                                                    <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold ${roleCfg.color}`}>
                                                        {roleCfg.icon}
                                                        {roleCfg.label}
                                                    </span>
                                                </td>

                                                {/* Cabang */}
                                                <td className="px-4 py-3 align-middle">
                                                    {cabangs.length === 0 ? (
                                                        <span className="text-[11px] text-muted-foreground">—</span>
                                                    ) : (
                                                        <div className="flex flex-wrap gap-1">
                                                            {cabangs.map((c) => (
                                                                <span
                                                                    key={c.id}
                                                                    className="inline-flex items-center gap-1 rounded-md border bg-muted/40 px-1.5 py-0.5 text-[10px] font-medium"
                                                                >
                                                                    <Building2 className="h-2.5 w-2.5 text-muted-foreground" />
                                                                    {c.nama ?? c.kode ?? String(c.id)}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    )}
                                                </td>

                                                {/* Status */}
                                                <td className="px-4 py-3 align-middle">
                                                    <span
                                                        className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ${
                                                            u.aktif
                                                                ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-400'
                                                                : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                                                        }`}
                                                    >
                                                        {u.aktif ? 'Aktif' : 'Nonaktif'}
                                                    </span>
                                                </td>

                                                {/* Aksi */}
                                                <td className="px-4 py-3 align-middle">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Button variant="ghost" size="icon" className="h-7 w-7" asChild title="Edit pengguna">
                                                            <Link href={admin.users.edit(u.id)}>
                                                                <Pencil className="h-3.5 w-3.5" />
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-7 w-7 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                                            title="Hapus pengguna"
                                                            onClick={() => handleDelete(u)}
                                                        >
                                                            <Trash2 className="h-3.5 w-3.5" />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}

                                    {(users?.data ?? []).length === 0 && (
                                        <tr>
                                            <td colSpan={5} className="px-4 py-12 text-center">
                                                <Users className="mx-auto mb-2 h-8 w-8 text-muted-foreground/40" />
                                                <p className="text-sm text-muted-foreground">Tidak ada pengguna ditemukan.</p>
                                                {hasFilter && (
                                                    <button
                                                        type="button"
                                                        onClick={reset}
                                                        className="mt-1 text-xs text-primary underline"
                                                    >
                                                        Reset filter
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* ── Pagination ── */}
                        <div className="flex items-center justify-between border-t px-4 py-3 text-sm">
                            <p className="text-xs text-muted-foreground">
                                Halaman <span className="font-medium text-foreground">{users?.current_page ?? 1}</span>{' '}
                                dari <span className="font-medium text-foreground">{users?.last_page ?? 1}</span>
                            </p>
                            <div className="flex items-center gap-1.5">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!users?.prev_page_url}
                                    asChild
                                >
                                    <Link href={users?.prev_page_url ?? '#'}>
                                        <ChevronLeft className="mr-1 h-3.5 w-3.5" />
                                        Sebelumnya
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!users?.next_page_url}
                                    asChild
                                >
                                    <Link href={users?.next_page_url ?? '#'}>
                                        Berikutnya
                                        <ChevronRight className="ml-1 h-3.5 w-3.5" />
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

            </div>
        </AppLayout>
    );
}
