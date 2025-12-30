import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';

interface ProdukItem {
    id: number;
    sku: string;
    nama: string;
    kelompok_nama?: string | null;
    varian?: string | null;
    tipe: string;
    harga_jual: number | string;
    aktif: boolean;
    image_path?: string | null;
    kategori?: { id: number; nama: string };
    stok_etalase?: Array<{
        cabang_id: number;
        jumlah: number;
        stok_minimum: number;
        cabang?: { nama: string };
    }>;
}

interface ProdukCardProps {
    produk: ProdukItem;
    canManageProduk?: boolean;
    formatHarga: (value: number | string) => string;
}

export default function ProdukCard({ produk, canManageProduk = false, formatHarga }: ProdukCardProps) {
    const getTipeColor = (tipe: string) => {
        switch (tipe) {
            case 'beans':
                return 'bg-amber-100 text-amber-800';
            case 'minuman':
                return 'bg-blue-100 text-blue-800';
            case 'snack':
                return 'bg-green-100 text-green-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const getStatusColor = (aktif: boolean) => {
        return aktif ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
    };

    const getTotalStok = () => {
        if (!produk.stok_etalase || produk.stok_etalase.length === 0) return 0;
        return produk.stok_etalase.reduce((sum, stok) => sum + stok.jumlah, 0);
    };

    const isStokRendah = () => {
        if (!produk.stok_etalase || produk.stok_etalase.length === 0) return false;
        return produk.stok_etalase.some(stok => stok.jumlah <= stok.stok_minimum);
    };

    return (
        <Card className="hover:shadow-md transition-shadow">
            <CardHeader className="pb-3">
                <div className="flex items-start justify-between gap-3">
                    <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 mb-2">
                            {produk.image_path ? (
                                <img
                                    src={`/storage/${produk.image_path}`}
                                    alt={produk.nama}
                                    className="h-12 w-12 rounded-lg object-cover flex-shrink-0"
                                />
                            ) : (
                                <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-muted text-xs text-muted-foreground flex-shrink-0">
                                    No img
                                </div>
                            )}
                            <div className="min-w-0">
                                <h3 className="font-semibold text-sm truncate">
                                    {produk.varian
                                        ? `${produk.varian} ${produk.kelompok_nama || produk.nama}`
                                        : produk.nama}
                                </h3>
                                <p className="text-xs text-muted-foreground font-mono">{produk.sku}</p>
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-1">
                            <Badge className={getTipeColor(produk.tipe)}>
                                {produk.tipe}
                            </Badge>
                            <Badge className={getStatusColor(produk.aktif)}>
                                {produk.aktif ? 'Aktif' : 'Nonaktif'}
                            </Badge>
                            {produk.kategori && (
                                <Badge variant="outline">{produk.kategori.nama}</Badge>
                            )}
                        </div>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="pt-0">
                <div className="space-y-3">
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">Harga Jual</span>
                        <span className="font-semibold text-sm">{formatHarga(produk.harga_jual)}</span>
                    </div>
                    
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">Total Stok</span>
                        <div className="flex items-center gap-2">
                            <span className={`text-sm font-medium ${
                                isStokRendah() ? 'text-orange-600' : 'text-green-600'
                            }`}>
                                {getTotalStok()}
                            </span>
                            {isStokRendah() && (
                                <Badge variant="destructive" className="text-xs">
                                    Stok Rendah
                                </Badge>
                            )}
                        </div>
                    </div>

                    {produk.stok_etalase && produk.stok_etalase.length > 0 && (
                        <div className="border-t pt-3">
                            <p className="text-xs text-muted-foreground mb-2">Stok per Cabang:</p>
                            <div className="space-y-1">
                                {produk.stok_etalase.slice(0, 3).map((stok, index) => (
                                    <div key={index} className="flex justify-between items-center text-xs">
                                        <span className="text-muted-foreground">
                                            {stok.cabang?.nama || `Cabang ${stok.cabang_id}`}
                                        </span>
                                        <span className={`font-medium ${
                                            stok.jumlah <= stok.stok_minimum ? 'text-orange-600' : 'text-green-600'
                                        }`}>
                                            {stok.jumlah}
                                        </span>
                                    </div>
                                ))}
                                {produk.stok_etalase.length > 3 && (
                                    <p className="text-xs text-muted-foreground text-center">
                                        +{produk.stok_etalase.length - 3} cabang lainnya
                                    </p>
                                )}
                            </div>
                        </div>
                    )}

                    {canManageProduk && (
                        <div className="flex gap-2 pt-3 border-t">
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => router.get(`/produk/${produk.id}`)}
                                className="flex-1"
                            >
                                Detail
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => router.get(`/produk/${produk.id}/edit`)}
                                className="flex-1"
                            >
                                Edit
                            </Button>
                            <Button
                                size="sm"
                                variant="destructive"
                                onClick={() => {
                                    const ok = window.confirm(`Hapus produk ${produk.nama}?`);
                                    if (!ok) return;
                                    router.delete(`/produk/${produk.id}`, {
                                        preserveScroll: true,
                                    });
                                }}
                                className="flex-1"
                            >
                                Hapus
                            </Button>
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
