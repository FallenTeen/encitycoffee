import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Grid3X3, List, Rows3, ChevronUp, ChevronDown, Search, X, AlertCircle } from 'lucide-react';
import { cn } from '@/lib/utils';

interface KategoriOption {
    id: number;
    nama: string;
}

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
    satuan_dasar: string;
    kategori?: { id: number; nama: string };
    stok_etalase?: Array<{ jumlah: number | string; stok_minimum?: number | string }>;
}

interface ProdukPaginator {
    data: ProdukItem[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface FilterAktif {
    cabang_id?: number | string | null;
    kategori_id?: number | string | null;
    tipe?: string | null;
    aktif?: boolean | string | null;
    search?: string | null;
    sort_by?: string | null;
    sort_dir?: 'asc' | 'desc' | null;
    per_page?: number | null;
}

interface Props {
    produks: ProdukPaginator;
    kategori_list: KategoriOption[];
    filter_aktif: FilterAktif;
    cabangList?: Array<{id: number, nama: string, kode: string}>;
    selectedCabang?: {id: number, nama: string, kode: string} | null;
    cacheInfo?: {has_cache: boolean, cache_key: string, ttl: number};
    canManageProduk?: boolean;
}

type ViewMode = 'table' | 'card' | 'compact';
type SortField = 'nama' | 'kategori' | 'harga_jual' | 'stok' | 'sku';
type SortDir = 'asc' | 'desc' | null;

const STORAGE_KEYS = {
    VIEW_MODE: 'produk_view_mode',
    PER_PAGE: 'produk_per_page',
    SORT_BY: 'produk_sort_by',
    SORT_DIR: 'produk_sort_dir',
};

export default function ProdukIndex({
    produks,
    kategori_list,
    filter_aktif,
    cabangList,
    selectedCabang,
    canManageProduk = false,
}: Props) {
    // Load saved preferences
    const [viewMode, setViewMode] = useState<ViewMode>(() => {
        if (typeof window === 'undefined') return 'table';
        return (localStorage.getItem(STORAGE_KEYS.VIEW_MODE) as ViewMode) || 'table';
    });

    const [perPage, setPerPage] = useState<number>(() => {
        if (typeof window === 'undefined') return 20;
        const saved = localStorage.getItem(STORAGE_KEYS.PER_PAGE);
        return saved ? parseInt(saved) : (filter_aktif?.per_page || 20);
    });

    const [sortBy, setSortBy] = useState<SortField | null>(() => {
        if (typeof window === 'undefined') return null;
        return (localStorage.getItem(STORAGE_KEYS.SORT_BY) as SortField) || (filter_aktif?.sort_by as SortField) || null;
    });

    const [sortDir, setSortDir] = useState<SortDir>(() => {
        if (typeof window === 'undefined') return null;
        return (localStorage.getItem(STORAGE_KEYS.SORT_DIR) as SortDir) || (filter_aktif?.sort_dir as SortDir) || null;
    });

    const [togglingId, setTogglingId] = useState<number | null>(null);
    const [showAdvancedFilters, setShowAdvancedFilters] = useState(false);
    const [clearAllError, setClearAllError] = useState<string | null>(null);
    const [isClearing, setIsClearing] = useState(false);
    const searchInputRef = useRef<HTMLInputElement>(null);
    const debounceTimeout = useRef<number | null>(null);
    const clearAllDebounceTimeout = useRef<number | null>(null);

    const aktifValue = filter_aktif?.aktif;
    const { data, setData, get, processing } = useForm({
        search: filter_aktif?.search ?? '',
        cabang_id: filter_aktif?.cabang_id ? String(filter_aktif.cabang_id) : '',
        kategori_id: filter_aktif?.kategori_id ? String(filter_aktif.kategori_id) : '__all__',
        tipe: filter_aktif?.tipe ?? '__all__',
        aktif: aktifValue === null || aktifValue === undefined ? '__all__' : typeof aktifValue === 'string' ? aktifValue : aktifValue ? '1' : '0',
        sort_by: sortBy || '',
        sort_dir: sortDir || '',
        per_page: perPage,
    });

    const kategoriOptions = useMemo(
        () => [{ id: 0, nama: 'Semua kategori' }].concat(kategori_list ?? []),
        [kategori_list],
    );

    // Save preferences to localStorage
    useEffect(() => {
        localStorage.setItem(STORAGE_KEYS.VIEW_MODE, viewMode);
    }, [viewMode]);

    useEffect(() => {
        localStorage.setItem(STORAGE_KEYS.PER_PAGE, String(perPage));
    }, [perPage]);

    useEffect(() => {
        if (sortBy) localStorage.setItem(STORAGE_KEYS.SORT_BY, sortBy);
        else localStorage.removeItem(STORAGE_KEYS.SORT_BY);
    }, [sortBy]);

    useEffect(() => {
        if (sortDir) localStorage.setItem(STORAGE_KEYS.SORT_DIR, sortDir);
        else localStorage.removeItem(STORAGE_KEYS.SORT_DIR);
    }, [sortDir]);

    // Real-time search with debounce (optional)
    useEffect(() => {
        if (debounceTimeout.current) {
            window.clearTimeout(debounceTimeout.current);
        }

        // Only debounce if search has value (optional auto-search)
        if (data.search && data.search.length > 2) {
            debounceTimeout.current = window.setTimeout(() => {
                submitFilters();
            }, 300);
        }

        return () => {
            if (debounceTimeout.current) {
                window.clearTimeout(debounceTimeout.current);
            }
            if (clearAllDebounceTimeout.current) {
                window.clearTimeout(clearAllDebounceTimeout.current);
            }
        };
    }, [data.search]);

    const submitFilters = () => {
        get('/produk', {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleSort = (field: SortField) => {
        let newDir: SortDir = 'asc';
        
        if (sortBy === field) {
            if (sortDir === 'asc') newDir = 'desc';
            else if (sortDir === 'desc') {
                // Third click: remove sort
                setSortBy(null);
                setSortDir(null);
                setData({ ...data, sort_by: '', sort_dir: '' });
                submitFilters();
                return;
            }
        }

        setSortBy(field);
        setSortDir(newDir);
        setData({ ...data, sort_by: field, sort_dir: newDir });
        
        // Submit with new sort
        get('/produk', {
            data: { ...data, sort_by: field, sort_dir: newDir },
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handlePerPageChange = (value: string) => {
        const newPerPage = parseInt(value);
        
        // Validate per_page value (min 1, max 1000)
        const validatedPerPage = Math.max(1, Math.min(1000, newPerPage));
        
        setPerPage(validatedPerPage);
        setData({ ...data, per_page: validatedPerPage });
        
        get('/produk', {
            data: { ...data, per_page: validatedPerPage },
            preserveState: true,
            preserveScroll: false,
            replace: true,
        });
    };

    const handleQuickFilter = (filterType: 'beans' | 'minuman' | 'snack' | 'stok_rendah' | 'aktif') => {
        const newData = { ...data };

        if (filterType === 'stok_rendah') {
            // Toggle stok rendah filter (handled in backend or client-side filter)
            // For now, just visual feedback
            return;
        } else if (filterType === 'aktif') {
            newData.aktif = data.aktif === '1' ? '' : '1';
        } else {
            newData.tipe = data.tipe === filterType ? '' : filterType;
        }

        setData(newData);
        get('/produk', {
            data: newData,
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const clearAllFilters = () => {
        // Clear any existing debounce timeout
        if (clearAllDebounceTimeout.current) {
            window.clearTimeout(clearAllDebounceTimeout.current);
        }
        
        // Set new debounce timeout (500ms)
        clearAllDebounceTimeout.current = window.setTimeout(() => {
            // Rate limiting: prevent multiple rapid requests
            if (isClearing) {
                return;
            }
            
            setIsClearing(true);
            setClearAllError(null);
            
            const resetData = {
                search: '',
                kategori_id: '__all__',
                tipe: '__all__',
                aktif: '__all__',
                sort_by: '',
                sort_dir: '',
                per_page: perPage,
                cabang_id: data.cabang_id,
            };
            setData(resetData);
            setSortBy(null);
            setSortDir(null);
            
            router.get('/produk', data.cabang_id ? { cabang_id: data.cabang_id, per_page: perPage } : { per_page: perPage }, {
                preserveScroll: true,
                replace: true,
                onError: (errors) => {
                    console.error('Error clearing filters:', errors);
                    setClearAllError('Gagal membersihkan filter. Silakan coba lagi.');
                    setIsClearing(false);
                    
                    // Log error for debugging
                    if (window.console && window.console.error) {
                        window.console.error('ClearAllFilters Error:', {
                            errors,
                            timestamp: new Date().toISOString(),
                            userAgent: navigator.userAgent,
                            url: window.location.href,
                        });
                    }
                },
                onSuccess: () => {
                    setIsClearing(false);
                    setClearAllError(null);
                },
            });
        }, 500);
    };

    const formatHarga = (value: number | string) => {
        const num = typeof value === 'string' ? Number(value) : value;
        if (Number.isNaN(num)) return '-';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(num);
    };

    const getStockStatus = (stok?: number | string) => {
        const stockNum = typeof stok === 'string' ? parseFloat(stok) : stok || 0;
        if (stockNum < 20) return { color: 'text-red-600 bg-red-50', label: 'Rendah', icon: '🔴' };
        if (stockNum < 50) return { color: 'text-yellow-600 bg-yellow-50', label: 'Sedang', icon: '🟡' };
        return { color: 'text-green-600 bg-green-50', label: 'Baik', icon: '🟢' };
    };

    const getImageUrl = (imagePath?: string | null) => {
        if (!imagePath) return null;
        return `/storage/${imagePath}`;
    };

    const activeFiltersCount = [
        data.search,
        data.kategori_id,
        data.tipe,
        data.aktif,
    ].filter(Boolean).length;

    const SortIcon = ({ field }: { field: SortField }) => {
        if (sortBy !== field) return <ChevronUp className="h-3 w-3 opacity-30" />;
        return sortDir === 'asc' ? (
            <ChevronUp className="h-3 w-3" />
        ) : (
            <ChevronDown className="h-3 w-3" />
        );
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Produk', href: '/produk' }]}>
            <Head title="Produk" />
            <div className="space-y-4">
                {/* Header */}
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Daftar Produk</h1>
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <span>{produks?.total ?? 0} produk</span>
                            {selectedCabang && (
                                <>
                                    <span>•</span>
                                    <span>{selectedCabang.nama} ({selectedCabang.kode})</span>
                                </>
                            )}
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        {/* View Mode Toggle */}
                        <div className="flex items-center rounded-md border">
                            <Button
                                variant={viewMode === 'table' ? 'default' : 'ghost'}
                                size="sm"
                                onClick={() => setViewMode('table')}
                                className="rounded-r-none"
                            >
                                <List className="h-4 w-4" />
                            </Button>
                            <Button
                                variant={viewMode === 'card' ? 'default' : 'ghost'}
                                size="sm"
                                onClick={() => setViewMode('card')}
                                className="rounded-none border-x"
                            >
                                <Grid3X3 className="h-4 w-4" />
                            </Button>
                            <Button
                                variant={viewMode === 'compact' ? 'default' : 'ghost'}
                                size="sm"
                                onClick={() => setViewMode('compact')}
                                className="rounded-l-none"
                            >
                                <Rows3 className="h-4 w-4" />
                            </Button>
                        </div>
                        {canManageProduk && (
                            <Button asChild>
                                <Link href="/produk/create">+ Tambah Produk</Link>
                            </Button>
                        )}
                    </div>
                </div>

                {/* Quick Filters */}
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-sm font-medium text-muted-foreground">Quick:</span>
                    <Badge
                        variant={data.tipe === 'beans' ? 'default' : 'outline'}
                        className="cursor-pointer"
                        onClick={() => handleQuickFilter('beans')}
                    >
                        Beans
                    </Badge>
                    <Badge
                        variant={data.tipe === 'minuman' ? 'default' : 'outline'}
                        className="cursor-pointer"
                        onClick={() => handleQuickFilter('minuman')}
                    >
                        Minuman
                    </Badge>
                    <Badge
                        variant={data.tipe === 'snack' ? 'default' : 'outline'}
                        className="cursor-pointer"
                        onClick={() => handleQuickFilter('snack')}
                    >
                        Snack
                    </Badge>
                    <Badge
                        variant={data.aktif === '1' ? 'default' : 'outline'}
                        className="cursor-pointer"
                        onClick={() => handleQuickFilter('aktif')}
                    >
                        Aktif Saja
                    </Badge>
                    {activeFiltersCount > 0 && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={clearAllFilters}
                            className="h-6 px-2 text-xs"
                            disabled={isClearing}
                        >
                            {isClearing ? 'Menghapus...' : `Clear All (${activeFiltersCount})`}
                        </Button>
                    )}
                </div>
                
                {/* Error Message */}
                {clearAllError && (
                    <div className="rounded-md bg-red-50 p-3 text-sm text-red-800">
                        <div className="flex items-center gap-2">
                            <AlertCircle className="h-4 w-4" />
                            <span>{clearAllError}</span>
                            <Button
                                variant="ghost"
                                size="sm"
                                className="ml-auto h-6 px-2"
                                onClick={() => setClearAllError(null)}
                            >
                                Tutup
                            </Button>
                        </div>
                    </div>
                )}

                {/* Search & Filters */}
                <div className="space-y-3 rounded-lg border bg-card p-4">
                    {/* Search Bar */}
                    <div className="relative">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            ref={searchInputRef}
                            value={data.search}
                            onChange={(e) => setData('search', e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                    e.preventDefault();
                                    submitFilters();
                                }
                            }}
                            placeholder="Cari produk (nama, SKU)... Press Enter untuk search"
                            className="pl-9 pr-9"
                        />
                        {data.search && (
                            <button
                                type="button"
                                onClick={() => {
                                    setData('search', '');
                                    searchInputRef.current?.focus();
                                }}
                                className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        )}
                    </div>

                    {/* Advanced Filters Toggle */}
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => setShowAdvancedFilters(!showAdvancedFilters)}
                        className="w-full"
                    >
                        {showAdvancedFilters ? 'Hide' : 'Show'} Advanced Filters
                    </Button>

                    {showAdvancedFilters && (
                        <div className="grid grid-cols-1 gap-3 pt-2 md:grid-cols-4">
                            {cabangList && cabangList.length > 0 && (
                                <div className="space-y-1">
                                    <Label className="text-xs">Cabang</Label>
                                    <Select
                                        value={data.cabang_id}
                                        onValueChange={(value) => setData('cabang_id', value)}
                                    >
                                        <SelectTrigger className="h-9">
                                            <SelectValue placeholder="Pilih cabang" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__all__">Semua cabang</SelectItem>
                                            {cabangList.map((cabang) => (
                                                <SelectItem key={cabang.id} value={String(cabang.id)}>
                                                    {cabang.nama} ({cabang.kode})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}

                            <div className="space-y-1">
                                <Label className="text-xs">Kategori</Label>
                                <Select
                                    value={data.kategori_id}
                                    onValueChange={(value) => setData('kategori_id', value === '__all__' ? '' : value)}
                                >
                                    <SelectTrigger className="h-9">
                                        <SelectValue placeholder="Semua" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__all__">Semua kategori</SelectItem>
                                        {kategoriOptions.map((k) => (
                                            <SelectItem key={k.id} value={String(k.id)}>
                                                {k.nama}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Tipe</Label>
                                <Select
                                    value={data.tipe}
                                    onValueChange={(value) => setData('tipe', value === '__all__' ? '' : value)}
                                >
                                    <SelectTrigger className="h-9">
                                        <SelectValue placeholder="Semua" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__all__">Semua tipe</SelectItem>
                                        <SelectItem value="beans">Beans</SelectItem>
                                        <SelectItem value="minuman">Minuman</SelectItem>
                                        <SelectItem value="snack">Snack</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Status</Label>
                                <Select
                                    value={data.aktif}
                                    onValueChange={(value) => setData('aktif', value === '__all__' ? '' : value)}
                                >
                                    <SelectTrigger className="h-9">
                                        <SelectValue placeholder="Semua" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__all__">Semua status</SelectItem>
                                        <SelectItem value="1">Aktif</SelectItem>
                                        <SelectItem value="0">Nonaktif</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex items-end gap-2 md:col-span-4">
                                <Button type="button" onClick={submitFilters} disabled={processing} className="flex-1">
                                    Apply Filters
                                </Button>
                                <Button type="button" variant="outline" onClick={clearAllFilters} disabled={isClearing}>
                                    {isClearing ? 'Menghapus...' : 'Reset'}
                                </Button>
                            </div>
                        </div>
                    )}
                </div>

                {/* Table View */}
                {viewMode === 'table' && (
                    <div className="rounded-lg border bg-card">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="px-4 py-3 text-left">
                                            <button
                                                type="button"
                                                onClick={() => handleSort('sku')}
                                                className="flex items-center gap-1 font-medium hover:text-primary"
                                            >
                                                SKU
                                                <SortIcon field="sku" />
                                            </button>
                                        </th>
                                        <th className="px-4 py-3 text-left">Gambar</th>
                                        <th className="px-4 py-3 text-left">
                                            <button
                                                type="button"
                                                onClick={() => handleSort('nama')}
                                                className="flex items-center gap-1 font-medium hover:text-primary"
                                            >
                                                Nama
                                                <SortIcon field="nama" />
                                            </button>
                                        </th>
                                        <th className="px-4 py-3 text-left">
                                            <button
                                                type="button"
                                                onClick={() => handleSort('kategori')}
                                                className="flex items-center gap-1 font-medium hover:text-primary"
                                            >
                                                Kategori
                                                <SortIcon field="kategori" />
                                            </button>
                                        </th>
                                        <th className="px-4 py-3 text-right">
                                            <button
                                                type="button"
                                                onClick={() => handleSort('harga_jual')}
                                                className="flex items-center gap-1 font-medium hover:text-primary ml-auto"
                                            >
                                                Harga
                                                <SortIcon field="harga_jual" />
                                            </button>
                                        </th>
                                        <th className="px-4 py-3 text-center">
                                            <button
                                                type="button"
                                                onClick={() => handleSort('stok')}
                                                className="flex items-center gap-1 font-medium hover:text-primary mx-auto"
                                            >
                                                Stok
                                                <SortIcon field="stok" />
                                            </button>
                                        </th>
                                        <th className="px-4 py-3 text-center">Status</th>
                                        <th className="px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(produks?.data ?? []).map((p) => {
                                        const stok = p.stok_etalase?.[0]?.jumlah;
                                        const stockStatus = getStockStatus(stok);
                                        const imageUrl = getImageUrl(p.image_path);

                                        return (
                                            <tr key={p.id} className="border-b last:border-0 hover:bg-muted/50">
                                                <td className="px-4 py-3">
                                                    <code className="text-xs">{p.sku}</code>
                                                </td>
                                                <td className="px-4 py-3">
                                                    {imageUrl ? (
                                                        <img
                                                            src={imageUrl}
                                                            alt={p.nama}
                                                            className="h-10 w-10 rounded object-cover"
                                                        />
                                                    ) : (
                                                        <div className="flex h-10 w-10 items-center justify-center rounded bg-muted text-xs text-muted-foreground">
                                                            No img
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="font-medium">
                                                        {p.varian ? `${p.varian} ${p.kelompok_nama || p.nama}` : p.nama}
                                                    </div>
                                                    {p.kelompok_nama && p.kelompok_nama !== p.nama && (
                                                        <div className="text-xs text-muted-foreground">
                                                            {p.kelompok_nama}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge variant="outline" className="text-xs">
                                                        {p.tipe === 'beans' ? 'Beans' : p.tipe === 'minuman' ? 'Minuman' : 'Snack'}
                                                    </Badge>
                                                    {p.kategori?.nama && (
                                                        <div className="mt-1 text-xs text-muted-foreground">
                                                            {p.kategori.nama}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-right font-medium">
                                                    {formatHarga(p.harga_jual)}
                                                </td>
                                                <td className="px-4 py-3 text-center">
                                                    <Badge variant="secondary" className={cn('text-xs', stockStatus.color)}>
                                                        {stockStatus.icon} {stok ?? '-'}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3 text-center">
                                                    <button
                                                        type="button"
                                                        className={cn(
                                                            'relative inline-flex h-5 w-9 items-center rounded-full transition-colors',
                                                            p.aktif ? 'bg-green-500' : 'bg-gray-300',
                                                            togglingId === p.id || !canManageProduk
                                                                ? 'cursor-not-allowed opacity-60'
                                                                : 'cursor-pointer'
                                                        )}
                                                        disabled={togglingId === p.id || !canManageProduk}
                                                        onClick={() => {
                                                            if (!canManageProduk) return;
                                                            const ok = window.confirm(
                                                                p.aktif ? `Nonaktifkan ${p.nama}?` : `Aktifkan ${p.nama}?`
                                                            );
                                                            if (!ok) return;
                                                            setTogglingId(p.id);
                                                            router.post(`/produk/${p.id}/toggle-aktif`, {}, {
                                                                preserveScroll: true,
                                                                preserveState: true,
                                                                onFinish: () => setTogglingId(null),
                                                            });
                                                        }}
                                                    >
                                                        <span
                                                            className={cn(
                                                                'inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform',
                                                                p.aktif ? 'translate-x-4' : 'translate-x-0.5'
                                                            )}
                                                        />
                                                    </button>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center justify-center gap-2">
                                                        <Link href={`/produk/${p.id}`} className="text-xs text-primary hover:underline">
                                                            Detail
                                                        </Link>
                                                        {canManageProduk && (
                                                            <>
                                                                <Link href={`/produk/${p.id}/edit`} className="text-xs text-primary hover:underline">
                                                                    Edit
                                                                </Link>
                                                                <button
                                                                    type="button"
                                                                    className="text-xs text-destructive hover:underline"
                                                                    onClick={() => {
                                                                        if (window.confirm(`Hapus ${p.nama}?`)) {
                                                                            router.delete(`/produk/${p.id}`, { preserveScroll: true });
                                                                        }
                                                                    }}
                                                                >
                                                                    Hapus
                                                                </button>
                                                            </>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                        {(produks?.data ?? []).length === 0 && (
                            <div className="py-12 text-center text-muted-foreground">
                                <p className="text-lg font-medium">Tidak ada produk ditemukan</p>
                                <p className="text-sm">Coba ubah filter atau tambah produk baru</p>
                            </div>
                        )}
                    </div>
                )}

                {/* Card View */}
                {viewMode === 'card' && (
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {(produks?.data ?? []).map((p) => {
                            const stok = p.stok_etalase?.[0]?.jumlah;
                            const stockStatus = getStockStatus(stok);
                            const imageUrl = getImageUrl(p.image_path);

                            return (
                                <div key={p.id} className="group rounded-lg border bg-card p-4 transition-shadow hover:shadow-md">
                                    <div className="flex gap-3">
                                        {imageUrl ? (
                                            <img
                                                src={imageUrl}
                                                alt={p.nama}
                                                className="h-20 w-20 rounded object-cover"
                                            />
                                        ) : (
                                            <div className="flex h-20 w-20 items-center justify-center rounded bg-muted text-xs text-muted-foreground">
                                                No Image
                                            </div>
                                        )}
                                        <div className="flex-1 space-y-1">
                                            <h3 className="font-semibold line-clamp-2">
                                                {p.varian ? `${p.varian} ${p.kelompok_nama || p.nama}` : p.nama}
                                            </h3>
                                            <div className="flex items-center gap-2">
                                                <Badge variant="outline" className="text-xs">
                                                    {p.tipe === 'beans' ? 'Beans' : p.tipe === 'minuman' ? 'Minuman' : 'Snack'}
                                                </Badge>
                                                <Badge variant="secondary" className={cn('text-xs', stockStatus.color)}>
                                                    {stockStatus.icon} {stok ?? '-'}
                                                </Badge>
                                            </div>
                                            <p className="text-lg font-bold text-primary">{formatHarga(p.harga_jual)}</p>
                                        </div>
                                    </div>
                                    <div className="mt-3 flex items-center justify-between border-t pt-3">
                                        <code className="text-xs text-muted-foreground">{p.sku}</code>
                                        <div className="flex items-center gap-2">
                                            <Link href={`/produk/${p.id}`}>
                                                <Button variant="outline" size="sm">Detail</Button>
                                            </Link>
                                            {canManageProduk && (
                                                <Link href={`/produk/${p.id}/edit`}>
                                                    <Button variant="default" size="sm">Edit</Button>
                                                </Link>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                        {(produks?.data ?? []).length === 0 && (
                            <div className="col-span-full py-12 text-center text-muted-foreground">
                                <p className="text-lg font-medium">Tidak ada produk ditemukan</p>
                                <p className="text-sm">Coba ubah filter atau tambah produk baru</p>
                            </div>
                        )}
                    </div>
                )}

                {/* Compact View */}
                {viewMode === 'compact' && (
                    <div className="space-y-2">
                        {(produks?.data ?? []).map((p) => {
                            const stok = p.stok_etalase?.[0]?.jumlah;
                            const stockStatus = getStockStatus(stok);

                            return (
                                <div key={p.id} className="flex items-center justify-between rounded-lg border bg-card p-3 hover:bg-muted/50">
                                    <div className="flex items-center gap-3">
                                        <Badge variant={p.aktif ? 'default' : 'secondary'} className="w-12 justify-center text-xs">
                                            {p.aktif ? 'ON' : 'OFF'}
                                        </Badge>
                                        <div>
                                            <div className="font-medium">
                                                {p.varian ? `${p.varian} ${p.kelompok_nama || p.nama}` : p.nama}
                                            </div>
                                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                <code>{p.sku}</code>
                                                <span>•</span>
                                                <span>{p.tipe}</span>
                                                <span>•</span>
                                                <span className={stockStatus.color}>
                                                    {stockStatus.icon} {stok ?? '-'} {p.satuan_dasar}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-4">
                                        <span className="font-semibold">{formatHarga(p.harga_jual)}</span>
                                        <div className="flex items-center gap-2">
                                            <Link href={`/produk/${p.id}`}>
                                                <Button variant="ghost" size="sm">Detail</Button>
                                            </Link>
                                            {canManageProduk && (
                                                <Link href={`/produk/${p.id}/edit`}>
                                                    <Button variant="outline" size="sm">Edit</Button>
                                                </Link>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                        {(produks?.data ?? []).length === 0 && (
                            <div className="py-12 text-center text-muted-foreground">
                                <p className="text-lg font-medium">Tidak ada produk ditemukan</p>
                                <p className="text-sm">Coba ubah filter atau tambah produk baru</p>
                            </div>
                        )}
                    </div>
                )}

                {/* Pagination */}
                <div className="flex flex-col gap-3 rounded-lg border bg-card p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2">
                            <Label className="text-xs">Show:</Label>
                            <Select value={String(perPage)} onValueChange={handlePerPageChange}>
                                <SelectTrigger className="h-8 w-20">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="10">10</SelectItem>
                                    <SelectItem value="20">20</SelectItem>
                                    <SelectItem value="50">50</SelectItem>
                                    <SelectItem value="100">100</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="text-sm text-muted-foreground">
                            Showing {produks?.from ?? 0}-{produks?.to ?? 0} of {produks?.total ?? 0}
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!produks?.prev_page_url}
                            onClick={() => {
                                if (produks?.prev_page_url) {
                                    router.get(produks.prev_page_url, {}, { preserveState: true, preserveScroll: false });
                                }
                            }}
                        >
                            Previous
                        </Button>
                        <div className="flex items-center gap-1 text-sm">
                            <span className="font-medium">{produks?.current_page ?? 1}</span>
                            <span className="text-muted-foreground">of</span>
                            <span className="font-medium">{produks?.last_page ?? 1}</span>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!produks?.next_page_url}
                            onClick={() => {
                                if (produks?.next_page_url) {
                                    router.get(produks.next_page_url, {}, { preserveState: true, preserveScroll: false });
                                }
                            }}
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}