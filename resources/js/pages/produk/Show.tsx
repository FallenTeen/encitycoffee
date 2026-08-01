import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Building2,
    Edit,
    Flame,
    Package,
    Snowflake,
    ImageIcon,
} from 'lucide-react';
import { ImagePreview } from '@/components/ImageUploader';

interface Kategori {
    id: number;
    nama: string;
    slug: string;
}

interface SatuanStok {
    id: number;
    cabang?: { id: number; nama?: string | null } | null;
    jumlah: number | string;
}

interface Produk {
    id: number;
    kategori_id: number;
    sku: string;
    nama: string;
    kelompok_nama?: string | null;
    varian?: string | null;
    deskripsi?: string | null;
    image_path?: string | null;
    image_url?: string | null;
    tipe: string;
    base?: string | null;
    satuan_dasar: string;
    harga_modal: number | string;
    harga_jual: number | string;
    perlu_kalibrasi?: boolean | number | null;
    aktif: boolean;
    cabang_id?: number | null;
    cabang?: { id: number; nama: string; kode: string } | null;
}

interface Props {
    produk: Produk;
    selectedCabang?: { id: number; nama: string; kode: string } | null;
    kategori: Kategori[];
    stok_tersedia: SatuanStok[];
    cabangList?: Array<{ id: number; nama: string; kode: string }>;
}

function formatRupiah(value: number | string | undefined | null) {
    const amount = Number(value ?? 0);
    if (Number.isNaN(amount)) {
        return 'Rp 0';
    }
    return amount.toLocaleString('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });
}

export default function ProdukShow({
    produk,
    selectedCabang,
    kategori,
    stok_tersedia,
    cabangList = [],
}: Props) {
    const TIPE_LABELS: Record<string, string> = {
        'beans': 'Beans',
        'minuman': 'Minuman',
        'snack': 'Snack',
        'makanan': 'Makanan',
    };
    const tipeLabel = TIPE_LABELS[produk.tipe] || produk.tipe;

    const kategoriNama = kategori.find(k => k.id === produk.kategori_id)?.nama || '-';
    const variantIcon = produk.varian === 'Hot'
        ? <Flame className="h-4 w-4 text-orange-500" />
        : produk.varian === 'Ice'
            ? <Snowflake className="h-4 w-4 text-blue-500" />
            : <Package className="h-4 w-4 text-muted-foreground" />;

    const imageUrl = produk.image_path
        ? `/storage/${produk.image_path}`
        : null;

    return (
        <AppLayout breadcrumbs={[
            { title: 'Produk', href: '/produk' },
            { title: produk.nama, href: `/produk/${produk.id}` }
        ]}>
            <Head title={`Detail Produk - ${produk.nama}`} />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/produk">
                                <ArrowLeft className="h-5 w-5" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-semibold">{produk.nama}</h1>
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <code className="text-xs">{produk.sku}</code>
                                {selectedCabang && (
                                    <>
                                        <span>•</span>
                                        <span>{selectedCabang.nama}</span>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                    <Button asChild>
                        <Link href={`/produk/${produk.id}/edit`}>
                            <Edit className="h-4 w-4 mr-2" />
                            Edit Produk
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* Left Column - Image & Basic Info */}
                    <div className="space-y-6">
                        {/* Image */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Gambar Produk</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {imageUrl ? (
                                    <div className="relative aspect-[4/3] overflow-hidden rounded-lg border bg-muted">
                                        <img
                                            src={imageUrl}
                                            alt={produk.nama}
                                            className="h-full w-full object-cover"
                                        />
                                    </div>
                                ) : (
                                    <div className="flex aspect-[4/3] items-center justify-center rounded-lg border bg-muted">
                                        <div className="text-center text-muted-foreground">
                                            <ImageIcon className="mx-auto h-12 w-12" />
                                            <p className="mt-2 text-sm">Tidak ada gambar</p>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Status */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Status</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-center gap-3">
                                    <Badge variant={produk.aktif ? 'default' : 'secondary'}
                                        className={produk.aktif ? 'bg-green-500' : 'bg-gray-400'}>
                                        {produk.aktif ? 'Aktif' : 'Nonaktif'}
                                    </Badge>
                                    {produk.perlu_kalibrasi && (
                                        <Badge variant="outline" className="border-orange-500 text-orange-600">
                                            Perlu Kalibrasi
                                        </Badge>
                                    )}
                                    {produk.cabang_id && !produk.cabang && (
                                        <Badge variant="outline" className="border-blue-500 text-blue-600">
                                            Legacy (Cabang {produk.cabang_id})
                                        </Badge>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Column - Details */}
                    <div className="space-y-6">
                        {/* Product Details */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Informasi Produk</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <p className="text-sm text-muted-foreground">Tipe</p>
                                        <div className="flex items-center gap-2">
                                            {variantIcon}
                                            <span className="font-medium">{tipeLabel}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground">Kategori</p>
                                        <p className="font-medium">{kategoriNama}</p>
                                    </div>
                                    {produk.varian && (
                                        <div>
                                            <p className="text-sm text-muted-foreground">Varian</p>
                                            <p className="font-medium">{produk.varian}</p>
                                        </div>
                                    )}
                                    {produk.kelompok_nama && produk.kelompok_nama !== produk.nama && (
                                        <div>
                                            <p className="text-sm text-muted-foreground">Kelompok</p>
                                            <p className="font-medium">{produk.kelompok_nama}</p>
                                        </div>
                                    )}
                                    <div>
                                        <p className="text-sm text-muted-foreground">Satuan Dasar</p>
                                        <p className="font-medium">{produk.satuan_dasar}</p>
                                    </div>
                                </div>

                                <Separator />

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <p className="text-sm text-muted-foreground">Harga Modal</p>
                                        <p className="font-medium">{formatRupiah(produk.harga_modal)}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground">Harga Jual</p>
                                        <p className="text-lg font-bold text-primary">{formatRupiah(produk.harga_jual)}</p>
                                    </div>
                                </div>

                                {produk.deskripsi && (
                                    <>
                                        <Separator />
                                        <div>
                                            <p className="text-sm text-muted-foreground">Deskripsi</p>
                                            <p className="mt-1 text-sm">{produk.deskripsi}</p>
                                        </div>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        {/* Stock Info */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Stok Tersedia</CardTitle>
                                <CardDescription>
                                    Stok produk di etalase cabang
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {stok_tersedia && stok_tersedia.length > 0 ? (
                                    <div className="space-y-3">
                                        {stok_tersedia.map((stok) => (
                                            <div key={stok.id} className="flex items-center justify-between rounded-lg border p-3">
                                                <div className="flex items-center gap-2">
                                                    <Building2 className="h-4 w-4 text-muted-foreground" />
                                                    <span className="text-sm">
                                                        {stok.cabang?.nama || `Cabang ${stok.cabang?.id || '-'}`}
                                                    </span>
                                                </div>
                                                <span className="font-bold">
                                                    {Number(stok.jumlah).toLocaleString('id-ID')} {produk.satuan_dasar}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        Tidak ada data stok etalase
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        {/* Branch Info */}
                        {produk.cabang && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Cabang Pembuat</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex items-center gap-2">
                                        <Building2 className="h-4 w-4 text-muted-foreground" />
                                        <span className="font-medium">{produk.cabang.nama}</span>
                                        <code className="text-xs text-muted-foreground">
                                            ({produk.cabang.kode})
                                        </code>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
