import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link, useForm } from '@inertiajs/react';
import admin from '@/routes/admin';
import { Building2 } from 'lucide-react';

export default function AdminCabangCreate() {
    const { data, setData, post, processing, errors } = useForm({
        kode: '',
        nama: '',
        alamat: '',
        telepon: '',
        aktif: true as boolean,
    });

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Cabang', href: admin.cabang.index().url },
                { title: 'Tambah Cabang', href: admin.cabang.create().url },
            ]}
        >
            <Head title="Admin - Tambah Cabang" />
            <div className="space-y-6">

                {/* ── Header ── */}
                <div className="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Tambah Cabang</h1>
                        <p className="text-sm text-muted-foreground">
                            Buat data cabang baru beserta informasi operasionalnya.
                        </p>
                    </div>
                    <Button variant="outline" size="sm" asChild className="w-fit">
                        <Link href={admin.cabang.index().url}>Kembali</Link>
                    </Button>
                </div>

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
                                post(admin.cabang.store().url);
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
                                        placeholder="Contoh: CBG-01"
                                        className="font-mono"
                                        autoFocus
                                    />
                                    <InputError message={errors.kode} />
                                    <p className="text-[11px] text-muted-foreground">
                                        Kode unik untuk identifikasi cabang. Maksimal 20 karakter.
                                    </p>
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
                                        placeholder="Nama lengkap cabang"
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
                                        placeholder="Contoh: 0812xxxxxxxx (opsional)"
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
                                    {processing ? 'Menyimpan...' : 'Simpan Cabang'}
                                </Button>
                                <Button type="button" variant="outline" size="sm" asChild>
                                    <Link href={admin.cabang.index().url}>Batal</Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

            </div>
        </AppLayout>
    );
}
