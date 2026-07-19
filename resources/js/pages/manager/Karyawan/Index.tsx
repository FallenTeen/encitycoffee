import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Tooltip, TooltipContent, TooltipTrigger, TooltipProvider } from '@/components/ui/tooltip';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    Users,
    UserPlus,
    Pencil,
    Trash2,
    Building2,
    Search,
    AlertTriangle,
    CheckCircle,
    XCircle,
    Info,
} from 'lucide-react';
import { useState } from 'react';
import Swal from 'sweetalert2';
import { cn } from '@/lib/utils';

interface Karyawan {
    id: number;
    name: string;
    email: string;
    role: string;
    aktif: boolean;
    cabang?: { id: number; nama: string; kode: string };
}

interface Branch {
    id: number;
    nama: string;
    kode: string;
}

interface UnassignedEmployee {
    id: number;
    name: string;
    email: string;
    role: string;
    aktif: boolean;
}

interface Props {
    employees: {
        data: Karyawan[];
        total: number;
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    assignedBranches: Branch[];
    unassignedEmployees: UnassignedEmployee[];
    filter_aktif: {
        role: string;
        cabang_id: string;
        search: string;
    };
    stats: {
        total_supervisors: number;
        total_kasirs: number;
    };
    error?: string;
}

export default function KaryawanIndex({
    employees,
    assignedBranches,
    unassignedEmployees,
    filter_aktif,
    stats,
    error
}: Props) {
    const [isAddModalOpen, setIsAddModalOpen] = useState(false);
    const [addMode, setAddMode] = useState<'assign_existing' | 'create_new'>('assign_existing');
    const [selectedEmployee, setSelectedEmployee] = useState<number | null>(null);
    const [selectedBranch, setSelectedBranch] = useState<string>('');
    const [search, setSearch] = useState(filter_aktif?.search || '');
    const [roleFilter, setRoleFilter] = useState(filter_aktif?.role || 'all');
    const [cabangFilter, setCabangFilter] = useState(filter_aktif?.cabang_id || 'all');

    const { data: formData, setData, post, processing, errors } = useForm({
        mode: 'assign_existing',
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'kasir',
        aktif: true,
        employee_id: '',
        cabang_id: '',
    });

    const handleSearch = () => {
        router.get('/manager/karyawan', {
            search: search || undefined,
            role: roleFilter !== 'all' ? roleFilter : undefined,
            cabang_id: cabangFilter !== 'all' ? cabangFilter : undefined,
        }, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const handlePageChange = (url: string | null) => {
        if (url) {
            router.get(url, {
                search: search || undefined,
                role: roleFilter !== 'all' ? roleFilter : undefined,
                cabang_id: cabangFilter !== 'all' ? cabangFilter : undefined,
            }, {
                preserveScroll: true,
            });
        }
    };

    const handleAddEmployee = () => {
        setData({
            ...formData,
            mode: addMode,
            employee_id: selectedEmployee ? String(selectedEmployee) : '',
            cabang_id: selectedBranch,
        });

        post('/manager/karyawan', {
            preserveScroll: true,
            onSuccess: () => {
                setIsAddModalOpen(false);
                setSelectedEmployee(null);
                setSelectedBranch('');
                Swal.fire('Berhasil', 'Karyawan berhasil ditugaskan ke cabang', 'success');
            },
        });
    };

    const handleDeleteEmployee = (employee: Karyawan) => {
        Swal.fire({
            title: 'Hapus Penugasan',
            text: `Apakah Anda yakin ingin menghapus penugasan ${employee.name} dari cabang ${employee.cabang?.nama}? Karyawan akan menjadi tidak terikat cabang manapun.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(`/manager/karyawan/${employee.id}`, {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire('Berhasil', 'Penugasan karyawan berhasil dihapus', 'success');
                    },
                    onError: (err) => {
                        Swal.fire('Gagal', err.error || 'Gagal menghapus penugasan', 'error');
                    },
                });
            }
        });
    };

    if (error) {
        return (
            <AppLayout breadcrumbs={[{ title: 'Manajemen Karyawan', href: '/manager/karyawan' }]}>
                <Head title="Manajemen Karyawan" />
                <div className="flex items-center justify-center min-h-[400px]">
                    <Card className="w-full max-w-md">
                        <CardContent className="pt-6">
                            <div className="text-center space-y-4">
                                <AlertTriangle className="h-12 w-12 text-yellow-500 mx-auto" />
                                <h2 className="text-lg font-semibold">Informasi</h2>
                                <p className="text-sm text-muted-foreground">{error}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={[{ title: 'Manajemen Karyawan', href: '/manager/karyawan' }]}>
            <Head title="Manajemen Karyawan" />
            <TooltipProvider>
                <div className="space-y-6">
                    {/* Header */}
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 className="text-xl font-semibold">Manajemen Karyawan</h1>
                            <p className="text-sm text-muted-foreground">
                                Kelola supervisor dan kasir di cabang Anda
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button onClick={() => setIsAddModalOpen(true)}>
                                <UserPlus className="h-4 w-4 mr-2" />
                                Tambah Karyawan
                            </Button>
                        </div>
                    </div>

                    {/* Stats */}
                    <div className="grid gap-4 md:grid-cols-3">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Total Supervisor
                                </CardTitle>
                                <Users className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-semibold">{stats?.total_supervisors || 0}</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Total Kasir
                                </CardTitle>
                                <Users className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-semibold">{stats?.total_kasirs || 0}</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Karyawan Tersedia
                                </CardTitle>
                                <UserPlus className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-semibold">{unassignedEmployees?.length || 0}</div>
                                <p className="text-xs text-muted-foreground">Belum terikat cabang manapun</p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Filters */}
                    <Card>
                        <CardContent className="pt-4">
                            <div className="grid gap-4 md:grid-cols-4">
                                <div className="space-y-1">
                                    <Label>Cari</Label>
                                    <div className="flex gap-2">
                                        <Input
                                            placeholder="Nama atau email..."
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                            onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                                        />
                                        <Button variant="outline" size="icon" onClick={handleSearch}>
                                            <Search className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                                <div className="space-y-1">
                                    <Label>Role</Label>
                                    <Select value={roleFilter} onValueChange={(v) => {
                                        setRoleFilter(v);
                                        handleSearch();
                                    }}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua Role</SelectItem>
                                            <SelectItem value="supervisor">Supervisor</SelectItem>
                                            <SelectItem value="kasir">Kasir</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-1">
                                    <Label>Cabang</Label>
                                    <Select value={cabangFilter} onValueChange={(v) => {
                                        setCabangFilter(v);
                                        handleSearch();
                                    }}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua Cabang</SelectItem>
                                            {assignedBranches?.map((branch) => (
                                                <SelectItem key={branch.id} value={String(branch.id)}>
                                                    {branch.kode ? `${branch.kode} - ` : ''}{branch.nama}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="flex items-end">
                                    <Button variant="outline" onClick={() => {
                                        setSearch('');
                                        setRoleFilter('all');
                                        setCabangFilter('all');
                                        router.get('/manager/karyawan', {}, {
                                            preserveScroll: true,
                                            replace: true,
                                        });
                                    }}>
                                        Reset
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Employee Table */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">
                                Daftar Karyawan ({employees?.total || 0})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {!employees?.data || employees.data.length === 0 ? (
                                <div className="text-center py-8 text-muted-foreground">
                                    <Users className="h-12 w-12 mx-auto mb-4 opacity-50" />
                                    <p>Belum ada karyawan yang ditugaskan ke cabang Anda.</p>
                                    <p className="text-sm mt-1">Klik "Tambah Karyawan" untuk menugaskan karyawan baru.</p>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full text-sm">
                                        <thead>
                                            <tr className="border-b">
                                                <th className="py-3 pr-4 text-left font-medium">Nama</th>
                                                <th className="py-3 pr-4 text-left font-medium">Email</th>
                                                <th className="py-3 pr-4 text-left font-medium">Role</th>
                                                <th className="py-3 pr-4 text-left font-medium">Cabang</th>
                                                <th className="py-3 pr-4 text-center font-medium">Status</th>
                                                <th className="py-3 pr-4 text-center font-medium">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {employees.data.map((emp) => (
                                                <tr key={emp.id} className="border-b hover:bg-muted/50">
                                                    <td className="py-3 pr-4">
                                                        <div className="font-medium">{emp.name}</div>
                                                    </td>
                                                    <td className="py-3 pr-4 text-muted-foreground">
                                                        {emp.email}
                                                    </td>
                                                    <td className="py-3 pr-4">
                                                        <Badge variant={emp.role === 'supervisor' ? 'default' : 'secondary'}>
                                                            {emp.role === 'supervisor' ? 'Supervisor' : 'Kasir'}
                                                        </Badge>
                                                    </td>
                                                    <td className="py-3 pr-4">
                                                        <div className="flex items-center gap-2">
                                                            <Building2 className="h-4 w-4 text-muted-foreground" />
                                                            <span>
                                                                {emp.cabang?.kode ? `${emp.cabang.kode} - ` : ''}
                                                                {emp.cabang?.nama || '-'}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td className="py-3 pr-4 text-center">
                                                        {emp.aktif ? (
                                                            <Badge variant="outline" className="bg-green-50 text-green-600 border-green-200">
                                                                <CheckCircle className="h-3 w-3 mr-1" />
                                                                Aktif
                                                            </Badge>
                                                        ) : (
                                                            <Badge variant="outline" className="bg-red-50 text-red-600 border-red-200">
                                                                <XCircle className="h-3 w-3 mr-1" />
                                                                Nonaktif
                                                            </Badge>
                                                        )}
                                                    </td>
                                                    <td className="py-3 pr-4 text-center">
                                                        <div className="flex items-center justify-center gap-1">
                                                            <Tooltip>
                                                                <TooltipTrigger asChild>
                                                                    <Button variant="ghost" size="icon" asChild>
                                                                        <Link href={`/manager/karyawan/${emp.id}/edit`}>
                                                                            <Pencil className="h-4 w-4" />
                                                                        </Link>
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>Edit Karyawan</p>
                                                                </TooltipContent>
                                                            </Tooltip>
                                                            <Tooltip>
                                                                <TooltipTrigger asChild>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        onClick={() => handleDeleteEmployee(emp)}
                                                                    >
                                                                        <Trash2 className="h-4 w-4 text-red-500" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>Hapus Penugasan</p>
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            {/* Pagination */}
                            {employees?.last_page > 1 && (
                                <div className="flex items-center justify-between mt-4">
                                    <div className="text-sm text-muted-foreground">
                                        Halaman {employees.current_page} dari {employees.last_page}
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={!employees.prev_page_url}
                                            onClick={() => handlePageChange(employees.prev_page_url)}
                                        >
                                            Previous
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={!employees.next_page_url}
                                            onClick={() => handlePageChange(employees.next_page_url)}
                                        >
                                            Next
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Add Employee Modal */}
                <Dialog open={isAddModalOpen} onOpenChange={setIsAddModalOpen}>
                    <DialogContent className="max-w-md">
                        <DialogHeader>
                            <DialogTitle>Tambah Karyawan</DialogTitle>
                            <DialogDescription>
                                Pilih karyawan yang belum terikat cabang untuk ditugaskan ke cabang Anda.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4">
                            {/* Mode Selection */}
                            <div className="space-y-2">
                                <Label>Mode Penugasan</Label>
                                <div className="flex gap-4">
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="radio"
                                            name="addMode"
                                            checked={addMode === 'assign_existing'}
                                            onChange={() => setAddMode('assign_existing')}
                                            className="mr-2"
                                        />
                                        <span className="text-sm">Tugaskan Karyawan Existing</span>
                                    </label>
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="radio"
                                            name="addMode"
                                            checked={addMode === 'create_new'}
                                            onChange={() => setAddMode('create_new')}
                                            className="mr-2"
                                        />
                                        <span className="text-sm">Buat Karyawan Baru</span>
                                    </label>
                                </div>
                            </div>

                            {addMode === 'assign_existing' ? (
                                <>
                                    {/* Select Employee */}
                                    <div className="space-y-2">
                                        <Label>Pilih Karyawan</Label>
                                        <select
                                            value={selectedEmployee?.toString() || ''}
                                            onChange={(e) => setSelectedEmployee(e.target.value ? parseInt(e.target.value) : null)}
                                            className="w-full h-10 px-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                                        >
                                            <option value="">-- Pilih Karyawan --</option>
                                            {unassignedEmployees?.length === 0 ? (
                                                <option value="" disabled>Tidak ada karyawan tersedia</option>
                                            ) : (
                                                unassignedEmployees?.map((emp) => (
                                                    <option key={emp.id} value={emp.id.toString()}>
                                                        {emp.name} ({emp.role === 'supervisor' ? 'Supervisor' : 'Kasir'})
                                                    </option>
                                                ))
                                            )}
                                        </select>
                                        {unassignedEmployees?.length === 0 && (
                                            <p className="text-xs text-yellow-600 flex items-center gap-1">
                                                <AlertTriangle className="h-3 w-3" />
                                                Semua karyawan sudah ditugaskan ke cabang
                                            </p>
                                        )}
                                    </div>
                                </>
                            ) : (
                                <>
                                    {/* Create New Employee Form */}
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Nama</Label>
                                        <Input
                                            id="name"
                                            value={formData.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            placeholder="Nama lengkap"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={formData.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            placeholder="email@example.com"
                                        />
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="password">Password</Label>
                                            <Input
                                                id="password"
                                                type="password"
                                                value={formData.password}
                                                onChange={(e) => setData('password', e.target.value)}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="password_confirmation">Konfirmasi</Label>
                                            <Input
                                                id="password_confirmation"
                                                type="password"
                                                value={formData.password_confirmation}
                                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                            />
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Role</Label>
                                        <Select value={formData.role} onValueChange={(v) => setData('role', v)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="kasir">Kasir</SelectItem>
                                                <SelectItem value="supervisor">Supervisor</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </>
                            )}

                            {/* Select Branch */}
                            <div className="space-y-2">
                                <Label>Cabang</Label>
                                <select
                                    value={selectedBranch}
                                    onChange={(e) => setSelectedBranch(e.target.value)}
                                    className="w-full h-10 px-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                                >
                                    <option value="">-- Pilih Cabang --</option>
                                    {assignedBranches?.map((branch) => (
                                        <option key={branch.id} value={branch.id.toString()}>
                                            {branch.kode ? `${branch.kode} - ` : ''}{branch.nama}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button variant="outline" onClick={() => setIsAddModalOpen(false)}>
                                Batal
                            </Button>
                            <Button
                                onClick={handleAddEmployee}
                                disabled={
                                    processing ||
                                    (addMode === 'assign_existing' && !selectedEmployee) ||
                                    !selectedBranch ||
                                    (addMode === 'create_new' && (!formData.name || !formData.email || !formData.password))
                                }
                            >
                                {addMode === 'assign_existing' ? 'Tugaskan' : 'Buat & Tugaskan'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </TooltipProvider>
        </AppLayout>
    );
}
