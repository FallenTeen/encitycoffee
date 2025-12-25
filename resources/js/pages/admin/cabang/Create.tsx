import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';
import { Head, Link, useForm } from '@inertiajs/react';

export default function AdminCabangCreate() {
  const { data, setData, post, processing, errors } = useForm({
    kode: '',
    nama: '',
    alamat: '',
    telepon: '',
    aktif: true,
  });

  return (
    <AppLayout title="Tambah Cabang">
      <Head title="Admin - Tambah Cabang" />
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <div className="text-sm text-muted-foreground">Buat cabang baru</div>
          <Link href={admin.cabang.index()} className="text-sm underline text-muted-foreground">
            Kembali
          </Link>
        </div>

        <div className="rounded-md border p-4">
          <form
            className="grid grid-cols-1 gap-4 md:grid-cols-2"
            onSubmit={(e) => {
              e.preventDefault();
              post(admin.cabang.store().url);
            }}
          >
            <div className="space-y-1">
              <Label htmlFor="kode">Kode</Label>
              <Input id="kode" value={data.kode} onChange={(e) => setData('kode', e.target.value)} placeholder="CTH: CBG-01" />
              <InputError message={errors.kode} />
            </div>

            <div className="space-y-1">
              <Label htmlFor="nama">Nama</Label>
              <Input id="nama" value={data.nama} onChange={(e) => setData('nama', e.target.value)} placeholder="Nama cabang" />
              <InputError message={errors.nama} />
            </div>

            <div className="space-y-1 md:col-span-2">
              <Label htmlFor="alamat">Alamat</Label>
              <Input id="alamat" value={data.alamat} onChange={(e) => setData('alamat', e.target.value)} placeholder="Alamat (opsional)" />
              <InputError message={errors.alamat} />
            </div>

            <div className="space-y-1">
              <Label htmlFor="telepon">Telepon</Label>
              <Input id="telepon" value={data.telepon} onChange={(e) => setData('telepon', e.target.value)} placeholder="Telepon (opsional)" />
              <InputError message={errors.telepon} />
            </div>

            <div className="space-y-1">
              <Label>Status</Label>
              <div className="flex items-center gap-2 pt-2">
                <Checkbox checked={data.aktif} onCheckedChange={(v) => setData('aktif', Boolean(v))} />
                <span className="text-sm">{data.aktif ? 'Aktif' : 'Nonaktif'}</span>
              </div>
              <InputError message={errors.aktif} />
            </div>

            <div className="md:col-span-2 flex gap-2 pt-2">
              <Button type="submit" disabled={processing}>
                Simpan
              </Button>
              <Button type="button" variant="secondary" asChild>
                <Link href={admin.cabang.index()}>Batal</Link>
              </Button>
            </div>
          </form>
        </div>
      </div>
    </AppLayout>
  );
}
