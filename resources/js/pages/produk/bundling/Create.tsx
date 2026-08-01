import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ArrowLeft, Plus, Trash2, Package, Box, Info } from 'lucide-react';
import { useMemo, useState } from 'react';

interface AvailableProduct {
    id: number;
    nama: string;
    sku: string;
    harga_jual: number | string;
}

interface CabangItem {
    id: number;
    nama: string;
    kode: string;
}

interface KategoriItem {
    id: number;
    nama: string;
}

interface Props {
    cabangList: CabangItem[];
    kategori_list: KategoriItem[];
    available_products: AvailableProduct[];
}

interface BundleItemForm {
    produk_id: number | '';
    jumlah: number;
}

const formatCurrency = (val: number) =>
    'Rp ' + val.toLocaleString('id-ID');

export default function BundlingCreate({ cabangList, kategori_list, available_products }: Props) {
    const [bundleItems, setBundleItems] = useState<BundleItemForm[]>([{ produk_id: '', jumlah: 1 }]);

    const { data, setData, post, processing, errors, transform } = useForm<{
        sku: string;
        nama: string;
        kategori_id: string;
        cabang_id: string;
        harga_jual: string;
        aktif: boolean;
        image: File | null;
        bundle_items: BundleItemForm[];
    }>({
        sku: 'BDL-',
        nama: '',
        kategori_id: kategori_list.find((k) => k.nama.toLowerCase() === 'bundling')?.id?.toString() || '',
        cabang_id: cabangList.length === 1 ? String(cabangList[0].id) : '',
        harga_jual: '',
        aktif: true,
        image: null,
        bundle_items: [],
    });

    // Calculate real total from bundleItems
    const realTotal = useMemo(() => {
        return bundleItems.reduce((sum, item) => {
            const produk = available_products.find((p) => p.id === item.produk_id);
            if (!produk) return sum;
            return sum + Number(produk.harga_jual) * item.jumlah;
        }, 0);
    }, [bundleItems, available_products]);

    const discount = Number(data.harga_jual || 0) > 0 ? realTotal - Number(data.harga_jual) : 0;

    const addItem = () =>
        setBundleItems((prev) => [...prev, { produk_id: '', jumlah: 1 }]);

    const removeItem = (idx: number) =>
        setBundleItems((prev) => prev.filter((_, i) => i !== idx));

    const updateItem = (idx: number, field: keyof BundleItemForm, value: number | string) => {
        setBundleItems((prev) =>
            prev.map((item, i) =>
                i === idx ? { ...item, [field]: field === 'jumlah' ? Number(value) : Number(value) } : item,
            ),
        );
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        transform((currentData) => ({
            ...currentData,
            bundle_items: bundleItems,
        }));
        
        post('/produk/bundling');
    };

    return (
        <AppLayout>
            <Head title="Buat Bundling" />
            <div className="p-6 max-w-3xl mx-auto space-y-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <Link href="/produk/bundling">
                        <Button variant="ghost" size="icon">
                            <ArrowLeft size={18} />
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold flex items-center gap-2">
                            <Box className="text-amber-500" size={24} />
                            Buat Bundling Menu
                        </h1>
                        <p className="text-sm text-muted-foreground">Gabungkan produk menjadi satu paket bundling</p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Basic Info Card */}
                    <div className="bg-card border rounded-xl p-5 space-y-4">
                        <h2 className="font-semibold text-sm uppercase tracking-wide text-muted-foreground">
                            Informasi Bundling
                        </h2>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="sku">SKU Bundling</Label>
                                <Input
                                    id="sku"
                                    value={data.sku}
                                    onChange={(e) => setData('sku', e.target.value)}
                                    placeholder="BDL-001"
                                />
                                {errors.sku && <p className="text-xs text-destructive">{errors.sku}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="nama">Nama Bundling</Label>
                                <Input
                                    id="nama"
                                    value={data.nama}
                                    onChange={(e) => setData('nama', e.target.value)}
                                    placeholder="Contoh: Paket Hemat Pagi"
                                />
                                {errors.nama && <p className="text-xs text-destructive">{errors.nama}</p>}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                                <Label>Kategori</Label>
                                <Select value={data.kategori_id} onValueChange={(v) => setData('kategori_id', v)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Default: Bundling" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {kategori_list.map((k) => (
                                            <SelectItem key={k.id} value={String(k.id)}>{k.nama}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            {cabangList.length > 1 && (
                                <div className="space-y-1.5">
                                    <Label>Cabang</Label>
                                    <Select value={data.cabang_id} onValueChange={(v) => setData('cabang_id', v)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih cabang" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {cabangList.map((c) => (
                                                <SelectItem key={c.id} value={String(c.id)}>{c.nama}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.cabang_id && <p className="text-xs text-destructive">{errors.cabang_id}</p>}
                                </div>
                            )}
                        </div>

                        <div className="flex items-center gap-3">
                            <Checkbox
                                id="aktif"
                                checked={data.aktif}
                                onCheckedChange={(v) => setData('aktif', Boolean(v))}
                            />
                            <Label htmlFor="aktif" className="cursor-pointer">Aktif</Label>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="image">Foto Bundling (opsional)</Label>
                            <Input
                                id="image"
                                type="file"
                                accept="image/*"
                                onChange={(e) => setData('image', e.target.files?.[0] ?? null)}
                                className="cursor-pointer"
                            />
                        </div>
                    </div>

                    {/* Bundle Items Card */}
                    <div className="bg-card border rounded-xl p-5 space-y-4">
                        <div className="flex items-center justify-between">
                            <h2 className="font-semibold text-sm uppercase tracking-wide text-muted-foreground">
                                Produk dalam Bundling
                            </h2>
                            <Button type="button" variant="outline" size="sm" onClick={addItem} className="gap-1">
                                <Plus size={14} /> Tambah Produk
                            </Button>
                        </div>

                        {errors.bundle_items && (
                            <p className="text-xs text-destructive">{errors.bundle_items}</p>
                        )}

                        <div className="space-y-3">
                            {bundleItems.map((item, idx) => {
                                const selectedProduk = available_products.find((p) => p.id === item.produk_id);
                                return (
                                    <div key={idx} className="flex gap-2 items-end">
                                        <div className="flex-1 space-y-1">
                                            {idx === 0 && <Label>Produk</Label>}
                                            <Select
                                                value={item.produk_id === '' ? '' : String(item.produk_id)}
                                                onValueChange={(v) => updateItem(idx, 'produk_id', v)}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Pilih produk..." />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {available_products.map((p) => (
                                                        <SelectItem key={p.id} value={String(p.id)}>
                                                            {p.nama} — {formatCurrency(Number(p.harga_jual))}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="w-20 space-y-1">
                                            {idx === 0 && <Label>Qty</Label>}
                                            <Input
                                                type="number"
                                                min={1}
                                                value={item.jumlah}
                                                onChange={(e) => updateItem(idx, 'jumlah', e.target.value)}
                                                className="text-center"
                                            />
                                        </div>
                                        <div className="w-32 space-y-1">
                                            {idx === 0 && <Label>Subtotal</Label>}
                                            <div className="h-9 flex items-center px-3 rounded-md border bg-muted text-sm text-muted-foreground whitespace-nowrap">
                                                {selectedProduk
                                                    ? formatCurrency(Number(selectedProduk.harga_jual) * item.jumlah)
                                                    : '-'}
                                            </div>
                                        </div>
                                        {bundleItems.length > 1 && (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="text-destructive hover:bg-destructive/10"
                                                onClick={() => removeItem(idx)}
                                            >
                                                <Trash2 size={15} />
                                            </Button>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Pricing Card */}
                    <div className="bg-card border rounded-xl p-5 space-y-4">
                        <h2 className="font-semibold text-sm uppercase tracking-wide text-muted-foreground">
                            Harga Bundling
                        </h2>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {/* Real total (read-only) */}
                            <div className="space-y-1.5">
                                <Label className="flex items-center gap-1">
                                    Total Harga Real
                                    <span title="Jumlah harga satuan semua produk dalam bundling">
                                        <Info size={13} className="text-muted-foreground" />
                                    </span>
                                </Label>
                                <div className="h-10 flex items-center px-3 rounded-md border bg-muted text-sm font-medium">
                                    {formatCurrency(realTotal)}
                                </div>
                                <p className="text-xs text-muted-foreground">Dihitung otomatis dari produk yang dipilih</p>
                            </div>

                            {/* Bundle price (editable) */}
                            <div className="space-y-1.5">
                                <Label htmlFor="harga_jual">Harga Bundling (yang dijual)</Label>
                                <Input
                                    id="harga_jual"
                                    type="number"
                                    min={0}
                                    value={data.harga_jual}
                                    onChange={(e) => setData('harga_jual', e.target.value)}
                                    placeholder="Masukkan harga jual bundling"
                                />
                                {errors.harga_jual && <p className="text-xs text-destructive">{errors.harga_jual}</p>}
                            </div>
                        </div>

                        {/* Discount summary */}
                        {discount > 0 && (
                            <div className="flex items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-700 dark:text-green-400">
                                <Package size={15} />
                                Pelanggan hemat <strong className="ml-1">{formatCurrency(discount)}</strong> dibanding beli satuan
                            </div>
                        )}
                        {discount < 0 && Number(data.harga_jual) > 0 && (
                            <div className="flex items-center gap-2 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-sm text-amber-700 dark:text-amber-400">
                                <Info size={15} />
                                Harga bundling lebih tinggi dari total real — pastikan ini disengaja
                            </div>
                        )}
                    </div>

                    {/* Submit */}
                    <div className="flex gap-3 justify-end">
                        <Link href="/produk/bundling">
                            <Button type="button" variant="outline">Batal</Button>
                        </Link>
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-amber-500 hover:bg-amber-600 text-white gap-2"
                        >
                            <Package size={15} />
                            {processing ? 'Menyimpan...' : 'Simpan Bundling'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}