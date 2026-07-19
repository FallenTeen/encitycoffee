import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import admin from '@/routes/admin';
import { Building2, Users, CalendarClock, AlertTriangle, UserPlus, UserMinus, Search } from 'lucide-react';

interface Cabang {
    id: number;
    kode: string;
    nama: string;
    alamat: string | null;
    telepon: string | null;
    aktif: boolean;
    status: boolean;
}

interface UserInfo {
    id: number;
    name: string;
    email: string;
    role: string;
    aktif?: boolean;
}

interface Props {
    cabang: Cabang;
    users: UserInfo[];
    users_count: number;
    shift_count: number;
    available_users: UserInfo[];
    current_manager: UserInfo | null;
    current_supervisor: UserInfo | null;
    available_managers: UserInfo[];
    available_supervisors: UserInfo[];
}

const ROLE_CONFIG: Record<string, { label: string; color: string }> = {
    manager:    { label: 'Manager',    color: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-400' },
    supervisor: { label: 'Supervisor', color: 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-400' },
    kasir:      { label: 'Kasir',      color: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' },
    admin:      { label: 'Admin',      color: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400' },
    it_support: { label: 'IT Support', color: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400' },
};

export default function AdminCabangEdit({ cabang, users, users_count, shift_count, available_users, current_manager, current_supervisor, available_managers, available_supervisors }: Props) {
    const [search, setSearch] = useState('');
    const [selectedUserId, setSelectedUserId] = useState<number | ''>('');
    const [addingUser, setAddingUser] = useState(false);
    const [removingId, setRemovingId] = useState<number | null>(null);
    const [selectedManagerId, setSelectedManagerId] = useState<number | ''>('');
    const [selectedSupervisorId, setSelectedSupervisorId] = useState<number | ''>('');
    const [settingRole, setSettingRole] = useState<string | null>(null);
    const currentAktif = cabang.status ?? cabang.aktif ?? true;

    const { data, setData, put, processing, errors } = useForm({
        kode: cabang.kode ?? '',
        nama: cabang.nama ?? '',
        alamat: cabang.alamat ?? '',
        telepon: cabang.telepon ?? '',
        aktif: currentAktif as boolean,
    });

    const willDeactivate = currentAktif && !data.aktif;

    const filteredAvailable = available_users.filter((u) =>
        search === '' ||
        u.name.toLowerCase().includes(search.toLowerCase()) ||
        u.email.toLowerCase().includes(search.toLowerCase())
    );

    const filteredAvailableManagers = available_managers.filter(u => !current_manager || u.id !== current_manager.id);
    const filteredAvailableSupervisors = available_supervisors.filter(u => !current_supervisor || u.id !== current_supervisor.id);

    function handleAttach() {
        if (!selectedUserId) return;
        console.log('attach url:', admin.cabang.users.attach(cabang.id).url); // add debug log
        setAddingUser(true);
        router.post(
            admin.cabang.users.attach(cabang.id).url,
            { user_id: selectedUserId },
            {
                preserveScroll: true,
                onFinish: () => { setAddingUser(false); setSelectedUserId(''); setSearch(''); },
            }
        );
    }

    function handleDetach(userId: number) {
        console.log('detach url:', admin.cabang.users.detach([cabang.id, userId]).url); // add debug log
        setRemovingId(userId);
        router.delete(admin.cabang.users.detach([cabang.id, userId]).url, {
            preserveScroll: true,
            onFinish: () => setRemovingId(null),
        });
    }

    function handleSetManager() {
        if (!selectedManagerId) return;
        setSettingRole('manager');
        router.post(
            admin.cabang.manager.set(cabang.id).url,
            { user_id: selectedManagerId },
            {
                preserveScroll: true,
                onFinish: () => { setSettingRole(null); setSelectedManagerId(''); },
            }
        );
    }

    function handleRemoveManager() {
        if (!current_manager) return;
        setSettingRole('manager');
        router.delete(admin.cabang.manager.remove(cabang.id).url, {
            preserveScroll: true,
            onFinish: () => setSettingRole(null),
        });
    }

    function handleSetSupervisor() {
        if (!selectedSupervisorId) return;
        setSettingRole('supervisor');
        router.post(
            admin.cabang.supervisor.set(cabang.id).url,
            { user_id: selectedSupervisorId },
            {
                preserveScroll: true,
                onFinish: () => { setSettingRole(null); setSelectedSupervisorId(''); },
            }
        );
    }

    function handleRemoveSupervisor() {
        if (!current_supervisor) return;
        setSettingRole('supervisor');
        router.delete(admin.cabang.supervisor.remove(cabang.id).url, {
            preserveScroll: true,
            onFinish: () => setSettingRole(null),
        });
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Cabang', href: admin.cabang.index().url },
                { title: cabang.nama, href: admin.cabang.show(cabang.id).url },
                { title: 'Edit', href: admin.cabang.edit(cabang.id).url },
            ]}
        >
            <Head title={`Admin - Edit ${cabang.nama}`} />
            <div className="space-y-6">

                {/* ── Header ── */}
                <div className="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Edit Cabang</h1>
                        <p className="text-sm text-muted-foreground">
                            Perbarui informasi cabang{' '}
                            <span className="font-medium text-foreground">{cabang.nama}</span>.
                        </p>
                    </div>
                    <Button variant="outline" size="sm" asChild className="w-fit">
                        <Link href={admin.cabang.show(cabang.id).url}>Kembali</Link>
                    </Button>
                </div>

                {/* ── Context Info ── */}
                <div className="grid gap-3 sm:grid-cols-2">
                    <div className="flex items-center gap-2.5 rounded-md border bg-muted/30 px-3 py-2.5">
                        <div className="flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-950">
                            <Users className="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p className="text-[11px] text-muted-foreground">User Terdaftar</p>
                            <p className="text-sm font-semibold">{users_count} user</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2.5 rounded-md border bg-muted/30 px-3 py-2.5">
                        <div className="flex h-7 w-7 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-950">
                            <CalendarClock className="h-3.5 w-3.5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <p className="text-[11px] text-muted-foreground">Total Shift</p>
                            <p className="text-sm font-semibold">{shift_count} shift</p>
                        </div>
                    </div>
                </div>

                {/* ── Deactivation Warning ── */}
                {willDeactivate && shift_count > 0 && (
                    <div className="flex items-start gap-2.5 rounded-md border border-amber-200 bg-amber-50 px-3 py-2.5 dark:border-amber-800 dark:bg-amber-950/30">
                        <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" />
                        <p className="text-xs text-amber-700 dark:text-amber-300">
                            Cabang ini memiliki <span className="font-semibold">{shift_count} shift</span>. Pastikan tidak ada shift yang sedang buka sebelum menonaktifkan cabang.
                        </p>
                    </div>
                )}

                {/* ── Form Card ── */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-950">
                                <Building2 className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            </div>
                            <CardTitle className="text-sm font-medium">Informasi Cabang</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                put(admin.cabang.update(cabang.id).url);
                            }}
                            className="space-y-5"
                        >
                            <div className="grid gap-5 md:grid-cols-2">

                                {/* Kode */}
                                <div className="space-y-1.5">
                                    <Label htmlFor="kode" className="text-xs font-medium">
                                        Kode <span className="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="kode"
                                        value={data.kode}
                                        onChange={(e) => setData('kode', e.target.value)}
                                        className="font-mono"
                                    />
                                    <InputError message={errors.kode} />
                                </div>

                                {/* Nama */}
                                <div className="space-y-1.5">
                                    <Label htmlFor="nama" className="text-xs font-medium">
                                        Nama Cabang <span className="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="nama"
                                        value={data.nama}
                                        onChange={(e) => setData('nama', e.target.value)}
                                    />
                                    <InputError message={errors.nama} />
                                </div>

                                {/* Alamat */}
                                <div className="space-y-1.5 md:col-span-2">
                                    <Label htmlFor="alamat" className="text-xs font-medium">Alamat</Label>
                                    <Input
                                        id="alamat"
                                        value={data.alamat}
                                        onChange={(e) => setData('alamat', e.target.value)}
                                        placeholder="Alamat lengkap cabang (opsional)"
                                    />
                                    <InputError message={errors.alamat} />
                                </div>

                                {/* Telepon */}
                                <div className="space-y-1.5">
                                    <Label htmlFor="telepon" className="text-xs font-medium">Nomor Telepon</Label>
                                    <Input
                                        id="telepon"
                                        value={data.telepon}
                                        onChange={(e) => setData('telepon', e.target.value)}
                                        placeholder="Nomor telepon (opsional)"
                                    />
                                    <InputError message={errors.telepon} />
                                </div>

                                {/* Status */}
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-medium">Status Operasional</Label>
                                    <div className="flex items-center gap-3 rounded-md border px-3 py-2.5">
                                        <button
                                            type="button"
                                            role="switch"
                                            aria-checked={data.aktif}
                                            onClick={() => setData('aktif', !data.aktif)}
                                            className={`relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors focus-visible:outline-none ${
                                                data.aktif
                                                    ? 'bg-green-500'
                                                    : 'bg-slate-300 dark:bg-slate-600'
                                            }`}
                                        >
                                            <span
                                                className={`inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform ${
                                                    data.aktif ? 'translate-x-4' : 'translate-x-0.5'
                                                }`}
                                            />
                                        </button>
                                        <div>
                                            <span className="text-sm font-medium">
                                                {data.aktif ? 'Aktif' : 'Nonaktif'}
                                            </span>
                                            <p className="text-[11px] text-muted-foreground">
                                                {data.aktif
                                                    ? 'Cabang dapat digunakan untuk operasional.'
                                                    : 'Cabang tidak akan tersedia untuk kasir dan supervisor.'}
                                            </p>
                                        </div>
                                    </div>
                                    <InputError message={errors.aktif} />
                                </div>

                            </div>

                            {/* Actions */}
                            <div className="flex items-center gap-2 border-t pt-4">
                                <Button type="submit" size="sm" disabled={processing}>
                                    {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                                </Button>
                                <Button type="button" variant="outline" size="sm" asChild>
                                    <Link href={admin.cabang.show(cabang.id).url}>Batal</Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* ── User Management Card ── */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-950">
                                <Users className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <CardTitle className="text-sm font-medium">Manajemen Pengguna</CardTitle>
                                <p className="text-[11px] text-muted-foreground mt-0.5">
                                    {users_count} pengguna terdaftar di cabang ini
                                </p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-5">

                        {/* ── Add User ── */}
                        <div className="space-y-2">
                            <p className="text-xs font-medium">Tambah Pengguna</p>
                            {available_users.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Semua pengguna aktif sudah terdaftar di cabang ini.
                                </p>
                            ) : (
                                <div className="flex flex-col gap-2 sm:flex-row">
                                    <div className="relative flex-1">
                                        <Search className="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                                        <Input
                                            placeholder="Cari nama atau email..."
                                            value={search}
                                            onChange={(e) => { setSearch(e.target.value); setSelectedUserId(''); }}
                                            className="pl-8 text-sm"
                                        />
                                    </div>
                                    <div className="flex gap-2">
                                        <select
                                            value={selectedUserId}
                                            onChange={(e) => setSelectedUserId(e.target.value ? Number(e.target.value) : '')}
                                            className="h-9 flex-1 rounded-md border border-input bg-background px-3 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring sm:w-52"
                                        >
                                            <option value="">-- Pilih pengguna --</option>
                                            {filteredAvailable.map((u) => (
                                                <option key={u.id} value={u.id}>
                                                    {u.name} ({ROLE_CONFIG[u.role]?.label ?? u.role})
                                                </option>
                                            ))}
                                        </select>
                                        <Button
                                            type="button"
                                            size="sm"
                                            disabled={!selectedUserId || addingUser}
                                            onClick={handleAttach}
                                            className="shrink-0"
                                        >
                                            <UserPlus className="mr-1.5 h-3.5 w-3.5" />
                                            {addingUser ? 'Menambahkan...' : 'Tambah'}
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* ── Current Users ── */}
                        <div className="space-y-2">
                            <p className="text-xs font-medium">Pengguna Saat Ini</p>
                            <p className="text-xs font-medium font-italic">Role di masing masing cabang otomatis terdaftar sebagai role akun (misal akun manager, akan menjadi manager di cabang ini sata didaftarkan).</p>
                            {users.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Belum ada pengguna di cabang ini.
                                </p>
                            ) : (
                                <div className="divide-y rounded-md border">
                                    {users
                                        .slice()
                                        .sort((a, b) => {
                                            const byRole = a.role.localeCompare(b.role);
                                            if (byRole !== 0) return byRole;
                                            return a.name.localeCompare(b.name);
                                        })
                                        .map((u) => {
                                            const cfg = ROLE_CONFIG[u.role] ?? { label: u.role, color: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' };
                                            return (
                                                <div
                                                    key={u.id}
                                                    className="flex items-center justify-between px-3 py-2.5 hover:bg-muted/30 transition-colors"
                                                >
                                                    <div className="min-w-0">
                                                        <p className="truncate text-sm font-medium">{u.name}</p>
                                                        <p className="truncate text-[11px] text-muted-foreground">{u.email}</p>
                                                    </div>
                                                    <div className="ml-3 flex shrink-0 items-center gap-2">
                                                        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ${cfg.color}`}>
                                                            {cfg.label}
                                                        </span>
                                                        <button
                                                            type="button"
                                                            onClick={() => handleDetach(u.id)}
                                                            disabled={removingId === u.id}
                                                            className="flex h-6 w-6 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive disabled:opacity-40"
                                                            title="Hapus dari cabang"
                                                        >
                                                            <UserMinus className="h-3.5 w-3.5" />
                                                        </button>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                </div>
                            )}
                        </div>

                    </CardContent>
                </Card>

                {/* ── Manajemen Manager & Supervisor ──
                
                
                */}
                

            </div>
        </AppLayout>
    );
}
