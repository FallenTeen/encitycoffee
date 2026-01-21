import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import supervisor from '@/routes/supervisor';
import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Eye, EyeOff } from 'lucide-react';

interface Props {
  cabang_list: Array<{ id: number; kode?: string; nama?: string }>;
}

export default function KasirCreate({ cabang_list }: Props) {
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
  const [showPassword, setShowPassword] = useState(false);
  const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);

  return (
    <AppLayout title="Tambah Kasir">
      <Head title="Supervisor - Tambah Kasir" />
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <div className="text-sm text-muted-foreground">Buat kasir baru</div>
          <Link href={supervisor.kasir.index()} className="text-sm underline text-muted-foreground">
            Kembali
          </Link>
        </div>

        <div className="rounded-md border p-4">
          <form
            className="grid grid-cols-1 gap-4 md:grid-cols-2"
            onSubmit={(e) => {
              e.preventDefault();
              post(supervisor.kasir.store().url);
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
              <div className="relative">
                <Input
                  id="password"
                  type={showPassword ? 'text' : 'password'}
                  value={data.password}
                  onChange={(e) => setData('password', e.target.value)}
                  className="pr-10"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((prev) => !prev)}
                  className="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-foreground focus-visible:outline-none"
                  aria-label={showPassword ? 'Sembunyikan password' : 'Tampilkan password'}
                  aria-pressed={showPassword}
                >
                  {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
              <InputError message={errors.password} />
            </div>
            <div className="space-y-1">
              <Label htmlFor="password_confirmation">Konfirmasi Password</Label>
              <div className="relative">
                <Input
                  id="password_confirmation"
                  type={showPasswordConfirmation ? 'text' : 'password'}
                  value={data.password_confirmation}
                  onChange={(e) => setData('password_confirmation', e.target.value)}
                  className="pr-10"
                />
                <button
                  type="button"
                  onClick={() => setShowPasswordConfirmation((prev) => !prev)}
                  className="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-foreground focus-visible:outline-none"
                  aria-label={showPasswordConfirmation ? 'Sembunyikan password' : 'Tampilkan password'}
                  aria-pressed={showPasswordConfirmation}
                >
                  {showPasswordConfirmation ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
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
                <Link href={supervisor.kasir.index()}>Batal</Link>
              </Button>
            </div>
          </form>
        </div>
      </div>
    </AppLayout>
  );
}
