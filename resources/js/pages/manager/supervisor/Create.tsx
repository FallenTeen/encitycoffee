import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import manager from '@/routes/manager';
import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo } from 'react';

interface Props {
  cabang_list: Array<{ id: number; kode?: string; nama?: string }>;
}

export default function ManagerSupervisorCreate({ cabang_list }: Props) {
  const cabangOptions = useMemo(
    () => (cabang_list ?? []).map((c) => ({ id: c.id, label: `${c.kode ?? c.id} - ${c.nama ?? '-'}` })),
    [cabang_list],
  );

  const { data, setData, post, processing, errors } = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    aktif: true,
    cabang_ids: [] as number[],
  });

  return (
    <AppLayout title="Tambah Supervisor">
      <Head title="Manager - Tambah Supervisor" />
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <div className="text-sm text-muted-foreground">Buat supervisor baru</div>
          <Link href={manager.supervisor.index()} className="text-sm underline text-muted-foreground">
            Kembali
          </Link>
        </div>

        <div className="rounded-md border p-4">
          <form
            className="grid grid-cols-1 gap-4 md:grid-cols-2"
            onSubmit={(e) => {
              e.preventDefault();
              post(manager.supervisor.store().url);
            }}
          >
            <div className="space-y-1">
              <Label htmlFor="name">Nama</Label>
              <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
              <InputError message={errors.name} />
            </div>
            <div className="space-y-1">
              <Label htmlFor="email">Email</Label>
              <Input id="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
              <InputError message={errors.email} />
            </div>

            <div className="space-y-1">
              <Label htmlFor="password">Password</Label>
              <Input
                id="password"
                type="password"
                value={data.password}
                onChange={(e) => setData('password', e.target.value)}
              />
              <InputError message={errors.password} />
            </div>
            <div className="space-y-1">
              <Label htmlFor="password_confirmation">Konfirmasi Password</Label>
              <Input
                id="password_confirmation"
                type="password"
                value={data.password_confirmation}
                onChange={(e) => setData('password_confirmation', e.target.value)}
              />
              <InputError message={errors.password_confirmation} />
            </div>

            <div className="space-y-1">
              <Label>Status</Label>
              <div className="flex items-center gap-2 pt-2">
                <Checkbox checked={data.aktif} onCheckedChange={(v) => setData('aktif', Boolean(v))} />
                <span className="text-sm">{data.aktif ? 'Aktif' : 'Nonaktif'}</span>
              </div>
              <InputError message={errors.aktif} />
            </div>

            <div className="md:col-span-2 space-y-2">
              <Label>Cabang</Label>
              <Select
                value={data.cabang_ids.length === 1 ? String(data.cabang_ids[0]) : ''}
                onValueChange={(v) => setData('cabang_ids', v ? [Number(v)] : [])}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Pilih cabang" />
                </SelectTrigger>
                <SelectContent>
                  {cabangOptions.map((c) => (
                    <SelectItem key={c.id} value={String(c.id)}>
                      {c.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={errors.cabang_ids as unknown as string} />
            </div>

            <div className="md:col-span-2 flex gap-2 pt-2">
              <Button type="submit" disabled={processing}>
                Simpan
              </Button>
              <Button type="button" variant="secondary" asChild>
                <Link href={manager.supervisor.index()}>Batal</Link>
              </Button>
            </div>
          </form>
        </div>
      </div>
    </AppLayout>
  );
}
