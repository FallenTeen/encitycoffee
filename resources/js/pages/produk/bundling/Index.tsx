import { Badge } from '@/components/ui/badge';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import LazyImage from '@/components/produk/LazyImage';
import ToggleStatusBadge from '@/components/produk/ToggleStatusBadge';
import SkeletonProductTable from '@/components/produk/SkeletonProductTable';
import SkeletonProductCard from '@/components/produk/SkeletonProductCard';
import SkeletonProductCompact from '@/components/produk/SkeletonProductCompact';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    AlertCircle,
    Box,
    Building2,
    ChevronDown,
    ChevronUp,
    Eye,
    Flame,
    Grid3X3,
    Layers,
    List,
    Package,
    Pencil,
    Plus,
    Rows3,
    Search,
    Snowflake,
    Trash2,
    X,
} from 'lucide-react';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import Swal from 'sweetalert2';
import { useEffect, useMemo, useRef, useState } from 'react';
import { useOutlet } from '@/contexts/OutletContext';

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
    stok_etalase?: Array<{
        jumlah: number | string;
        stok_minimum?: number | string;
    }>;
    branch_id?: number | null;
    cabang?: { id: number; nama: string; kode: string } | null;
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

interface BundleItemEntry {
    id: number;
    jumlah: number;
    produk: {
        id: number;
        nama: string;
        sku: string;
        harga_jual: number | string;
    };
}

interface BundleProduk {
    id: number;
    sku: string;
    nama: string;
    harga_jual: number | string;
    aktif: boolean;
    image_path?: string | null;
    bundle_items: BundleItemEntry[];
}

interface Props {
    produks: ProdukPaginator;
    kategori_list: KategoriOption[];
    filter_aktif: FilterAktif;
    cabangList?: Array<{ id: number; nama: string; kode: string }>;
    selectedCabang?: { id: number; nama: string; kode: string } | null;
    cacheInfo?: { has_cache: boolean; cache_key: string; ttl: number };
    canManageProduk?: boolean;
    canDeleteProduk?: boolean;
    bundlings?: {
        data: BundleProduk[];
        total: number;
    };
}

type ViewMode = 'table' | 'card' | 'compact';
type SortField = 'nama' | 'kategori' | 'harga_jual' | 'stok' | 'sku';
type SortDir = 'asc' | 'desc' | null;
type GroupMode = 'list' | 'grouped';

const STORAGE_KEYS = {
    VIEW_MODE: 'produk_view_mode',
    PER_PAGE: 'produk_per_page',
    SORT_BY: 'produk_sort_by',
    SORT_DIR: 'produk_sort_dir',
    GROUP_MODE: 'produk_group_mode',
};

interface QuickFiltersState {
    tipe: string[];
    aktif: boolean;
    stok_rendah: boolean;
}

export default function ProdukIndex({
    produks,
    kategori_list,
    filter_aktif,
    cabangList,
    selectedCabang,
    canManageProduk = false,
    canDeleteProduk = false,
    bundlings,
}: Props) {
    // Get outlet context for persistent switching
    const { selectedOutlet, outlets, switchOutlet } = useOutlet();

    // Combine outlets from context and props
    const allOutlets = outlets.length > 0 ? outlets : (cabangList || []);

    // Sync selected outlet with filter
    useEffect(() => {
        if (selectedOutlet?.id && filter_aktif?.cabang_id !== selectedOutlet.id) {
            // Switch to the selected outlet
            setData((prev) => ({ ...prev, cabang_id: String(selectedOutlet.id) }));
        }
    }, [selectedOutlet]);

    // Handle outlet change from context
    const handleOutletChange = (outletId: number | null) => {
        if (outletId) {
            switchOutlet(outletId);
            setData((prev) => ({ ...prev, cabang_id: String(outletId) }));
            // Reload data with new outlet
            router.get('/produk', { cabang_id: outletId }, {
                preserveState: true,
                preserveScroll: true,
            });
        } else {
            setData((prev) => ({ ...prev, cabang_id: '' }));
            router.get('/produk', {}, {
                preserveState: true,
                preserveScroll: true,
            });
        }
    };

    // Load saved preferences
    const [viewMode, setViewMode] = useState<ViewMode>(() => {
        if (typeof window === 'undefined') return 'table';
        return (
            (localStorage.getItem(STORAGE_KEYS.VIEW_MODE) as ViewMode) ||
            'table'
        );
    });

    const [perPage, setPerPage] = useState<number>(() => {
        if (typeof window === 'undefined') return 20;
        const saved = localStorage.getItem(STORAGE_KEYS.PER_PAGE);
        return saved ? parseInt(saved) : filter_aktif?.per_page || 20;
    });

    const [sortBy, setSortBy] = useState<SortField | null>(() => {
        if (typeof window === 'undefined') return null;
        return (
            (localStorage.getItem(STORAGE_KEYS.SORT_BY) as SortField) ||
            (filter_aktif?.sort_by as SortField) ||
            null
        );
    });

    const [sortDir, setSortDir] = useState<SortDir>(() => {
        if (typeof window === 'undefined') return null;
        return (
            (localStorage.getItem(STORAGE_KEYS.SORT_DIR) as SortDir) ||
            (filter_aktif?.sort_dir as SortDir) ||
            null
        );
    });

    const [groupMode, setGroupMode] = useState<GroupMode>(() => {
        if (typeof window === 'undefined') return 'list';
        return (
            (localStorage.getItem(STORAGE_KEYS.GROUP_MODE) as GroupMode) ||
            'list'
        );
    });

    const [togglingId, setTogglingId] = useState<number | null>(null);
    const [showAdvancedFilters, setShowAdvancedFilters] = useState(false);
    const [clearAllError, setClearAllError] = useState<string | null>(null);
    const [isClearing, setIsClearing] = useState(false);
    const [isCabangModalOpen, setIsCabangModalOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [activeTab, setActiveTab] = useState<'produk' | 'bundling'>('produk');
    const [bundlingSearch, setBundlingSearch] = useState('');
    const [bundlingViewMode, setBundlingViewMode] = useState<'grid' | 'list'>(() => {
        if (typeof window === 'undefined') return 'grid';
        return (localStorage.getItem('bundling_view_mode') as 'grid' | 'list') || 'grid';
    });
    const [bundlingPage, setBundlingPage] = useState(1);
    const [bundlingPerPage, setBundlingPerPage] = useState<number>(() => {
        if (typeof window === 'undefined') return 12;
        const saved = localStorage.getItem('bundling_per_page');
        return saved ? parseInt(saved) : 12;
    });
    const [quickFilters, setQuickFilters] = useState<QuickFiltersState>({
        tipe: [],
        aktif: false,
        stok_rendah: false,
    });
    const searchInputRef = useRef<HTMLInputElement>(null);
    const debounceTimeout = useRef<number | null>(null);
    const clearAllDebounceTimeout = useRef<number | null>(null);

    const aktifValue = filter_aktif?.aktif;
    const { data, setData, get, processing } = useForm({
        search: filter_aktif?.search ?? '',
        cabang_id: filter_aktif?.cabang_id
            ? String(filter_aktif.cabang_id)
            : '',
        kategori_id: filter_aktif?.kategori_id
            ? String(filter_aktif.kategori_id)
            : '__all__',
        tipe: filter_aktif?.tipe ?? '__all__',
        aktif:
            aktifValue === null || aktifValue === undefined
                ? '__all__'
                : typeof aktifValue === 'string'
                  ? aktifValue
                  : aktifValue
                    ? '1'
                    : '0',
        sort_by: sortBy || '',
        sort_dir: sortDir || '',
        per_page: perPage,
    });

    const applyQuickFilters = useMemo(() => {
        const result = produks?.data ?? [];
        return result;
    }, [produks?.data]);

    const filteredData = useMemo(() => {
        let result = applyQuickFilters;

        if (quickFilters.tipe.length > 0) {
            result = result.filter((p) => quickFilters.tipe.includes(p.tipe));
        }

        if (quickFilters.aktif) {
            result = result.filter((p) => p.aktif);
        }

        return result;
    }, [applyQuickFilters, quickFilters]);

    const quickFiltersCount =
        quickFilters.tipe.length +
        (quickFilters.aktif ? 1 : 0) +
        (quickFilters.stok_rendah ? 1 : 0);

    const activeFiltersCount =
        [
            data.search,
            data.kategori_id,
            data.tipe,
            data.aktif,
        ].filter(Boolean).length + quickFiltersCount;

    const handlePagination = (url: string | null) => {
        if (!url) return;

        router.get(
            url,
            {},
            {
                preserveState: true,
                preserveScroll: false,
                onError: (errors) => {
                    console.error('Pagination error:', errors);
                },
            },
        );
    };

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

    useEffect(() => {
        localStorage.setItem(STORAGE_KEYS.GROUP_MODE, groupMode);
    }, [groupMode]);

    const handleToggleQuickFilter = (
        filterType: 'tipe' | 'aktif' | 'stok_rendah',
        value?: string,
    ) => {
        if (filterType === 'tipe' && value) {
            setQuickFilters((prev) => ({
                ...prev,
                tipe: prev.tipe.includes(value)
                    ? prev.tipe.filter((t) => t !== value)
                    : [...prev.tipe, value],
            }));
        } else {
            setQuickFilters((prev) => ({
                ...prev,
                [filterType]: !prev[filterType],
            }));
        }
    };

    const handleDeleteProduk = async (produk: ProdukItem) => {
        const result = await Swal.fire({
            title: 'Hapus Produk',
            text: `Apakah Anda yakin ingin menghapus "${produk.nama}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            focusCancel: true,
        });

        if (!result.isConfirmed) {
            return;
        }

        router.delete(`/produk/${produk.id}`, {
            preserveScroll: true,
        });
    };

    const handleToggleStatus = (produk: ProdukItem) => {
        if (!canManageProduk) return;

        setTogglingId(produk.id);

        router.post(
            `/produk/${produk.id}/toggle-aktif`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({ only: ['produks'] });
                },
                onError: (errors) => {
                    const message =
                        Object.values(errors)[0] ?? 'Gagal mengubah status produk. Silakan coba lagi.';
                    alert(message);
                },
                onFinish: () => {
                    setTogglingId(null);
                },
            },
        );
    };

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

        // Cleanup function
        return () => {
            if (debounceTimeout.current) {
                window.clearTimeout(debounceTimeout.current);
            }
        };
    }, [data.search]);

    const submitFilters = () => {
        get('/produk', {
            preserveState: viewMode === 'table',
            preserveScroll: true,
            replace: true,
            onStart: () => setIsLoading(true),
            onFinish: () => setIsLoading(false),
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

        const nextData = { ...data, sort_by: field, sort_dir: newDir };

        setSortBy(field);
        setSortDir(newDir);
        setData(nextData);

        get('/produk', {
            preserveState: viewMode === 'table',
            preserveScroll: true,
            replace: true,
            onStart: () => setIsLoading(true),
            onFinish: () => setIsLoading(false),
        });
    };

    const handlePerPageChange = (value: string) => {
        const newPerPage = parseInt(value);

        // Validate per_page value (min 1, max 1000)
        if (isNaN(newPerPage) || newPerPage < 1) {
            console.error('Invalid per_page value:', value);
            return;
        }

        const validatedPerPage = Math.max(1, Math.min(1000, newPerPage));

        setPerPage(validatedPerPage);
        setData('per_page', validatedPerPage);

        // Build params directly from current data + new per_page to avoid stale state
        const params: Record<string, unknown> = {};
        if (data.search) params.search = data.search;
        if (data.cabang_id) params.cabang_id = data.cabang_id;
        if (data.kategori_id && data.kategori_id !== '__all__') params.kategori_id = data.kategori_id;
        if (data.tipe && data.tipe !== '__all__') params.tipe = data.tipe;
        if (data.aktif && data.aktif !== '__all__') params.aktif = data.aktif;
        if (data.sort_by) params.sort_by = data.sort_by;
        if (data.sort_dir) params.sort_dir = data.sort_dir;
        params.per_page = validatedPerPage;
        params.page = 1;

        router.get('/produk', params, {
            preserveState: viewMode === 'table',
            preserveScroll: false,
            replace: true,
            onStart: () => setIsLoading(true),
            onFinish: () => setIsLoading(false),
        });
    };

    const clearAllFilters = () => {
        // Prevent multiple rapid clicks
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
        setQuickFilters({
            tipe: [],
            aktif: false,
            stok_rendah: false,
        });

        // Use router.get with proper params
        const params: any = { per_page: perPage };
        if (data.cabang_id) {
            params.cabang_id = data.cabang_id;
        }

        router.get('/produk', params, {
            preserveState: viewMode === 'table',
            preserveScroll: true,
            replace: true,
            onStart: () => setIsLoading(true),
            onError: (errors) => {
                console.error('Error clearing filters:', errors);
                setClearAllError(
                    'Gagal membersihkan filter. Silakan coba lagi.',
                );
                setIsClearing(false);
            },
            onSuccess: () => {
                setIsClearing(false);
                setClearAllError(null);
            },
            onFinish: () => {
                setIsClearing(false);
                setIsLoading(false);
            },
        });
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
        const stockNum =
            typeof stok === 'string' ? parseFloat(stok) : stok || 0;
        if (stockNum < 20)
            return {
                color: 'text-red-600 bg-red-50',
                label: 'Rendah',
                icon: '🔴',
            };
        if (stockNum < 50)
            return {
                color: 'text-yellow-600 bg-yellow-50',
                label: 'Sedang',
                icon: '🟡',
            };
        return {
            color: 'text-green-600 bg-green-50',
            label: 'Baik',
            icon: '🟢',
        };
    };

    const getImageUrl = (imagePath?: string | null) => {
        if (!imagePath) return null;
        // Don't prepend /storage/ here - LazyImage's resolveImageUrl will handle it
        // This prevents double /storage/ prefix
        return imagePath;
    };

    const groupedProducts = useMemo(() => {
        if (groupMode === 'list') return null;

        const groups: Record<string, ProdukItem[]> = {};

        filteredData.forEach((produk) => {
            const key = `${produk.kategori?.id || 0}_${
                produk.kelompok_nama || produk.nama
            }`;
            if (!groups[key]) {
                groups[key] = [];
            }
            groups[key].push(produk);
        });

        return groups;
    }, [filteredData, groupMode]);

    const SortIcon = ({ field }: { field: SortField }) => {
        if (sortBy !== field)
            return <ChevronUp className="h-3 w-3 opacity-30" />;
        return sortDir === 'asc' ? (
            <ChevronUp className="h-3 w-3" />
        ) : (
            <ChevronDown className="h-3 w-3" />
        );
    };

    const formatBundlingCurrency = (val: number | string) =>
        'Rp ' + Number(val).toLocaleString('id-ID');

    const filteredBundlings = useMemo(() => {
        if (!bundlings?.data) return [];
        if (!bundlingSearch.trim()) return bundlings.data;
        const q = bundlingSearch.toLowerCase();
        return bundlings.data.filter(
            (b) => b.nama.toLowerCase().includes(q) || b.sku.toLowerCase().includes(q),
        );
    }, [bundlings, bundlingSearch]);

    useEffect(() => {
        localStorage.setItem('bundling_view_mode', bundlingViewMode);
    }, [bundlingViewMode]);

    useEffect(() => {
        localStorage.setItem('bundling_per_page', String(bundlingPerPage));
    }, [bundlingPerPage]);

    // Reset to page 1 whenever the search term or page size changes
    useEffect(() => {
        setBundlingPage(1);
    }, [bundlingSearch, bundlingPerPage]);

    const bundlingTotalPages = Math.max(
        1,
        Math.ceil(filteredBundlings.length / bundlingPerPage),
    );

    // Keep current page in range if the filtered list shrinks
    useEffect(() => {
        if (bundlingPage > bundlingTotalPages) {
            setBundlingPage(bundlingTotalPages);
        }
    }, [bundlingTotalPages, bundlingPage]);

    const paginatedBundlings = useMemo(() => {
        const start = (bundlingPage - 1) * bundlingPerPage;
        return filteredBundlings.slice(start, start + bundlingPerPage);
    }, [filteredBundlings, bundlingPage, bundlingPerPage]);

    const bundlingShowingFrom =
        filteredBundlings.length === 0 ? 0 : (bundlingPage - 1) * bundlingPerPage + 1;
    const bundlingShowingTo = Math.min(
        bundlingPage * bundlingPerPage,
        filteredBundlings.length,
    );

    const handleDeleteBundling = (id: number, nama: string) => {
        Swal.fire({
            title: 'Hapus Bundling?',
            text: `"${nama}" akan dihapus permanen.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(`/produk/bundling/${id}`, {
                    onSuccess: () =>
                        Swal.fire({ title: 'Dihapus!', icon: 'success', timer: 1500, showConfirmButton: false }),
                });
            }
        });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Produk', href: '/produk' }]}>
            <Head title="Produk" />
            <div className="space-y-4">
                {/* Header */}
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {activeTab === 'produk' ? 'Daftar Produk' : 'Bundling Menu'}
                        </h1>
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            {activeTab === 'produk' ? (
                                <span>{produks?.total ?? 0} produk</span>
                            ) : (
                                <span>{bundlings?.total ?? 0} bundling</span>
                            )}
                            {selectedCabang && (
                                <>
                                    <span>•</span>
                                    <span>
                                        {selectedCabang.nama} (
                                        {selectedCabang.kode})
                                    </span>
                                </>
                            )}
                            {activeTab === 'produk' && activeFiltersCount > 0 && (
                                <Badge variant="secondary">
                                    {activeFiltersCount} filter aktif
                                </Badge>
                            )}
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        {/* Tab Switcher */}
                        <div className="flex items-center rounded-md border">
                            <Button
                                variant={activeTab === 'produk' ? 'default' : 'ghost'}
                                size="sm"
                                onClick={() => setActiveTab('produk')}
                                className="rounded-r-none gap-1.5"
                            >
                                <Package className="h-4 w-4" />
                                Produk
                            </Button>
                            <Button
                                variant={activeTab === 'bundling' ? 'default' : 'ghost'}
                                size="sm"
                                onClick={() => setActiveTab('bundling')}
                                className="rounded-l-none border-l gap-1.5"
                            >
                                <Layers className="h-4 w-4" />
                                Bundling
                                {(bundlings?.total ?? 0) > 0 && (
                                    <span className={cn(
                                        'ml-1 inline-flex h-5 min-w-[20px] items-center justify-center rounded-full px-1.5 text-xs font-semibold',
                                        activeTab === 'bundling'
                                            ? 'bg-white/20 text-white'
                                            : 'bg-muted text-muted-foreground'
                                    )}>
                                        {bundlings?.total}
                                    </span>
                                )}
                            </Button>
                        </div>

                        {/* Produk-only controls */}
                        {activeTab === 'produk' && (
                            <>
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
                                {/* Group Mode Toggle */}
                                <div className="flex items-center rounded-md border">
                                    <Button
                                        variant={groupMode === 'list' ? 'default' : 'ghost'}
                                        size="sm"
                                        onClick={() => setGroupMode('list')}
                                        className="rounded-r-none"
                                    >
                                        List All
                                    </Button>
                                    <Button
                                        variant={groupMode === 'grouped' ? 'default' : 'ghost'}
                                        size="sm"
                                        onClick={() => setGroupMode('grouped')}
                                        className="rounded-l-none"
                                    >
                                        <Package className="mr-1 h-4 w-4" />
                                        Grouped
                                    </Button>
                                </div>
                            </>
                        )}

                        {/* Bundling-only controls */}
                        {activeTab === 'bundling' && (
                            <div className="flex items-center rounded-md border">
                                <Button
                                    variant={bundlingViewMode === 'grid' ? 'default' : 'ghost'}
                                    size="sm"
                                    onClick={() => setBundlingViewMode('grid')}
                                    className="rounded-r-none"
                                >
                                    <Grid3X3 className="h-4 w-4" />
                                </Button>
                                <Button
                                    variant={bundlingViewMode === 'list' ? 'default' : 'ghost'}
                                    size="sm"
                                    onClick={() => setBundlingViewMode('list')}
                                    className="rounded-l-none border-l"
                                >
                                    <List className="h-4 w-4" />
                                </Button>
                            </div>
                        )}

                        {/* Outlet picker (always visible) */}
                        {allOutlets.length > 0 && (
                            <Dialog
                                open={isCabangModalOpen}
                                onOpenChange={setIsCabangModalOpen}
                            >
                                <DialogTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                    >
                                        <Building2 className="h-4 w-4 mr-2" />
                                        {selectedCabang ? selectedCabang.nama : 'Pilih Outlet'}
                                    </Button>
                                </DialogTrigger>
                                <DialogContent className="max-w-3xl">
                                    <DialogHeader>
                                        <DialogTitle>
                                            Katalog Produk Berdasarkan Outlet
                                        </DialogTitle>
                                        <DialogDescription>
                                            Pilih outlet untuk membuka katalog
                                            produk tersebut. Outlet yang dipilih
                                            akan diingat untuk navigasi selanjutnya.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <div className="mt-4 space-y-3">
                                        {/* All outlets option */}
                                        <div
                                            className={cn(
                                                'flex items-center justify-between rounded border px-3 py-2 cursor-pointer transition-colors',
                                                !data.cabang_id || data.cabang_id === ''
                                                    ? 'bg-primary/10 border-primary'
                                                    : 'bg-muted/40 hover:bg-muted'
                                            )}
                                            onClick={() => {
                                                handleOutletChange(null);
                                                setIsCabangModalOpen(false);
                                            }}
                                        >
                                            <div className="flex flex-col">
                                                <span className="font-medium">
                                                    Semua Outlet
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    Lihat produk dari semua outlet
                                                </span>
                                            </div>
                                            {!data.cabang_id && (
                                                <Badge variant="default">Aktif</Badge>
                                            )}
                                        </div>

                                        {allOutlets.map((cabang) => (
                                            <div
                                                key={cabang.id}
                                                className={cn(
                                                    'flex items-center justify-between rounded border px-3 py-2 cursor-pointer transition-colors',
                                                    data.cabang_id === String(cabang.id)
                                                        ? 'bg-primary/10 border-primary'
                                                        : 'bg-muted/40 hover:bg-muted'
                                                )}
                                                onClick={() => {
                                                    handleOutletChange(cabang.id);
                                                    setIsCabangModalOpen(false);
                                                }}
                                            >
                                                <div className="flex flex-col">
                                                    <span className="font-medium">
                                                        {cabang.nama}
                                                    </span>
                                                    <span className="text-xs text-muted-foreground">
                                                        Kode: {cabang.kode || 'N/A'}
                                                    </span>
                                                </div>
                                                {data.cabang_id === String(cabang.id) && (
                                                    <Badge variant="default">Aktif</Badge>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </DialogContent>
                            </Dialog>
                        )}

                        {/* Action buttons — per tab */}
                        {activeTab === 'produk' && canManageProduk && (
                            <Button asChild>
                                <Link href="/produk/create">
                                    <Plus className="h-4 w-4 mr-1" /> Tambah Produk
                                </Link>
                            </Button>
                        )}
                        {activeTab === 'bundling' && canManageProduk && (
                            <Button asChild>
                                <Link href="/produk/bundling/create">
                                    <Plus className="h-4 w-4 mr-1" /> Buat Bundling
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {/* ── BUNDLING TAB CONTENT ─────────────────── */}
                {activeTab === 'bundling' && (
                    <div className="space-y-4">
                        {/* Bundling Search */}
                        <div className="relative max-w-sm">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <input
                                className="w-full rounded-md border bg-background pl-9 pr-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
                                placeholder="Cari bundling..."
                                value={bundlingSearch}
                                onChange={(e) => setBundlingSearch(e.target.value)}
                            />
                        </div>

                        {/* Bundling Grid / List */}
                        {filteredBundlings.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-20 text-muted-foreground gap-3">
                                <Box size={48} className="opacity-30" />
                                <p className="font-medium">
                                    {bundlingSearch ? 'Tidak ada bundling yang cocok' : 'Belum ada bundling menu'}
                                </p>
                                <p className="text-sm">Buat paket bundling dari produk yang tersedia</p>
                                {canManageProduk && !bundlingSearch && (
                                    <Button asChild className="gap-2">
                                        <Link href="/produk/bundling/create">
                                            <Plus size={14} /> Buat Bundling Pertama
                                        </Link>
                                    </Button>
                                )}
                                {bundlingSearch && (
                                    <Button variant="ghost" size="sm" onClick={() => setBundlingSearch('')}>
                                        Reset pencarian
                                    </Button>
                                )}
                            </div>
                        ) : bundlingViewMode === 'list' ? (
                            <div className="space-y-2">
                                {paginatedBundlings.map((bundle) => {
                                    const realTotal = bundle.bundle_items.reduce(
                                        (sum, item) => sum + Number(item.produk?.harga_jual ?? 0) * item.jumlah, 0
                                    );
                                    const discount = realTotal - Number(bundle.harga_jual);
                                    return (
                                        <div
                                            key={bundle.id}
                                            className="flex items-center justify-between gap-3 rounded-lg border bg-card p-3 hover:bg-muted/50"
                                        >
                                            <div className="flex min-w-0 items-center gap-3">
                                                <div className="h-12 w-12 shrink-0 overflow-hidden rounded-md bg-muted flex items-center justify-center">
                                                    {bundle.image_path ? (
                                                        <img
                                                            src={`/storage/${bundle.image_path.replace(/^\/?(storage\/)?/, '')}`}
                                                            alt={bundle.nama}
                                                            className="h-full w-full object-cover"
                                                            onError={(e) => { (e.target as HTMLImageElement).style.display = 'none'; }}
                                                        />
                                                    ) : (
                                                        <Box size={18} className="text-muted-foreground opacity-40" />
                                                    )}
                                                </div>
                                                <div className="min-w-0">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-medium truncate">{bundle.nama}</span>
                                                        <Badge
                                                            variant={bundle.aktif ? 'default' : 'secondary'}
                                                            className="text-[10px] px-1.5 py-0 shrink-0"
                                                        >
                                                            {bundle.aktif ? 'Aktif' : 'Nonaktif'}
                                                        </Badge>
                                                    </div>
                                                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                        <code>{bundle.sku}</code>
                                                        <span>•</span>
                                                        <span>{bundle.bundle_items.length} produk</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="flex shrink-0 items-center gap-4">
                                                <div className="text-right">
                                                    {discount > 0 && (
                                                        <div className="text-xs text-muted-foreground line-through">
                                                            {formatBundlingCurrency(realTotal)}
                                                        </div>
                                                    )}
                                                    <div className="font-semibold">
                                                        {formatBundlingCurrency(bundle.harga_jual)}
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-1">
                                                    {canManageProduk && (
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button asChild variant="ghost" size="icon">
                                                                    <Link
                                                                        href={`/produk/bundling/${bundle.id}/edit`}
                                                                        aria-label="Edit Bundling"
                                                                    >
                                                                        <Pencil className="h-4 w-4" aria-hidden="true" />
                                                                    </Link>
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>Edit Bundling</TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {canDeleteProduk && (
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                                    onClick={() => handleDeleteBundling(bundle.id, bundle.nama)}
                                                                    aria-label="Hapus Bundling"
                                                                >
                                                                    <Trash2 className="h-4 w-4" aria-hidden="true" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>Hapus Bundling</TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                {paginatedBundlings.map((bundle) => {
                                    const realTotal = bundle.bundle_items.reduce(
                                        (sum, item) => sum + Number(item.produk?.harga_jual ?? 0) * item.jumlah, 0
                                    );
                                    const discount = realTotal - Number(bundle.harga_jual);
                                    return (
                                        <div
                                            key={bundle.id}
                                            className="bg-card border rounded-xl p-4 flex flex-col gap-3 shadow-sm hover:shadow-md transition-shadow relative"
                                        >
                                            {/* BUNDLING badge */}
                                            <span className="absolute top-2 right-2 bg-primary text-primary-foreground text-[10px] font-bold px-2 py-0.5 rounded-full tracking-wide">BUNDLING</span>

                                            {/* Image */}
                                            <div className="w-full h-36 rounded-lg overflow-hidden bg-muted flex items-center justify-center">
                                                {bundle.image_path ? (
                                                    <img
                                                        src={`/storage/${bundle.image_path.replace(/^\/?(storage\/)?/, '')}`}
                                                        alt={bundle.nama}
                                                        className="w-full h-full object-cover"
                                                        onError={(e) => { (e.target as HTMLImageElement).style.display = 'none'; }}
                                                    />
                                                ) : (
                                                    <Box size={40} className="text-muted-foreground opacity-60" />
                                                )}
                                            </div>

                                            {/* Info */}
                                            <div className="flex-1">
                                                <div className="flex items-start justify-between gap-2">
                                                    <div>
                                                        <p className="font-semibold leading-tight">{bundle.nama}</p>
                                                        <p className="text-xs text-muted-foreground mt-0.5">{bundle.sku}</p>
                                                    </div>
                                                    <Badge
                                                        variant={bundle.aktif ? 'default' : 'secondary'}
                                                        className="text-xs shrink-0"
                                                    >
                                                        {bundle.aktif ? 'Aktif' : 'Nonaktif'}
                                                    </Badge>
                                                </div>

                                                {/* Items list */}
                                                <div className="mt-2 space-y-0.5">
                                                    {bundle.bundle_items.map((item) => (
                                                        <p key={item.id} className="text-xs text-muted-foreground">
                                                            • {item.produk?.nama} ×{item.jumlah}
                                                        </p>
                                                    ))}
                                                </div>

                                                {/* Pricing */}
                                                <div className="mt-3 pt-3 border-t space-y-1">
                                                    <div className="flex justify-between text-xs text-muted-foreground">
                                                        <span>Total real</span>
                                                        <span className="line-through">{formatBundlingCurrency(realTotal)}</span>
                                                    </div>
                                                    <div className="flex justify-between text-sm font-semibold">
                                                        <span>Harga bundling</span>
                                                        <span className="text-primary">{formatBundlingCurrency(bundle.harga_jual)}</span>
                                                    </div>
                                                    {discount > 0 && (
                                                        <div className="flex justify-between text-xs text-green-600">
                                                            <span>Hemat</span>
                                                            <span>{formatBundlingCurrency(discount)}</span>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>

                                            {/* Actions */}
                                            {(canManageProduk || canDeleteProduk) && (
                                                <div className="flex gap-2">
                                                    {canManageProduk && (
                                                        <Link href={`/produk/bundling/${bundle.id}/edit`} className="flex-1">
                                                            <Button variant="outline" size="sm" className="w-full gap-1">
                                                                <Pencil size={13} /> Edit
                                                            </Button>
                                                        </Link>
                                                    )}
                                                    {canDeleteProduk && (
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            className="gap-1"
                                                            onClick={() => handleDeleteBundling(bundle.id, bundle.nama)}
                                                        >
                                                            <Trash2 size={13} />
                                                        </Button>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        )}

                        {/* Pagination info */}
                        {(bundlings?.total ?? 0) > 0 && (
                            <p className="text-sm text-muted-foreground text-center">
                                Menampilkan {filteredBundlings.length} dari {bundlings?.total} bundling
                            </p>
                        )}
                    </div>
                )}

                {/* ── PRODUK TAB CONTENT ─────────────────── */}
                {activeTab === 'produk' && (
                    <>
                {/* Quick Filters */}
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-sm font-medium text-muted-foreground">
                        Quick:
                    </span>
                    {(['beans', 'minuman', 'snack', 'makanan'] as const).map((tipe) => (
                        <Badge
                            key={tipe}
                            variant={quickFilters.tipe.includes(tipe) ? 'default' : 'outline'}
                            className={cn(
                                'cursor-pointer transition-all capitalize',
                                quickFilters.tipe.includes(tipe) && 'ring-2 ring-primary ring-offset-1',
                            )}
                            onClick={() => handleToggleQuickFilter('tipe', tipe)}
                        >
                            {tipe.charAt(0).toUpperCase() + tipe.slice(1)}{' '}
                            {quickFilters.tipe.includes(tipe) ? '✓' : null}
                        </Badge>
                    ))}
                    <Badge
                        variant={quickFilters.aktif ? 'default' : 'outline'}
                        className={cn(
                            'cursor-pointer transition-all',
                            quickFilters.aktif && 'ring-2 ring-primary ring-offset-1',
                        )}
                        onClick={() => handleToggleQuickFilter('aktif')}
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
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
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
                            className="pr-9 pl-9"
                        />
                        {data.search && (
                            <button
                                type="button"
                                onClick={() => {
                                    setData('search', '');
                                    searchInputRef.current?.focus();
                                }}
                                className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        )}
                    </div>

                    {/* Advanced Filters Toggle */}
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() =>
                            setShowAdvancedFilters(!showAdvancedFilters)
                        }
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
                                        onValueChange={(value) =>
                                            setData('cabang_id', value)
                                        }
                                    >
                                        <SelectTrigger className="h-9">
                                            <SelectValue placeholder="Pilih cabang" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__all__">
                                                Semua cabang
                                            </SelectItem>
                                            {cabangList.map((cabang) => (
                                                <SelectItem
                                                    key={cabang.id}
                                                    value={String(cabang.id)}
                                                >
                                                    {cabang.nama} ({cabang.kode}
                                                    )
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}

                            <div className="space-y-1">
                                <Label className="text-xs">Kategori</Label>
                                <Select
                                    value={data.kategori_id || '__all__'}
                                    onValueChange={(value) => {
                                        // Normalize the value
                                        const normalizedValue =
                                            value === '__all__' || value === '0'
                                                ? ''
                                                : value;
                                        setData('kategori_id', normalizedValue);
                                    }}
                                >
                                    <SelectTrigger className="h-9">
                                        <SelectValue placeholder="Semua" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__all__">
                                            Semua kategori
                                        </SelectItem>
                                        {kategoriOptions
                                            .filter((k) => k.id !== 0)
                                            .map((k) => (
                                                <SelectItem
                                                    key={k.id}
                                                    value={String(k.id)}
                                                >
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
                                    onValueChange={(value) =>
                                        setData(
                                            'tipe',
                                            value === '__all__' ? '' : value,
                                        )
                                    }
                                >
                                    <SelectTrigger className="h-9">
                                        <SelectValue placeholder="Semua" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__all__">
                                            Semua tipe
                                        </SelectItem>
                                        <SelectItem value="beans">
                                            Beans
                                        </SelectItem>
                                        <SelectItem value="minuman">
                                            Minuman
                                        </SelectItem>
                                        <SelectItem value="snack">
                                            Snack
                                        </SelectItem>
                                        <SelectItem value="makanan">
                                            Makanan
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Status</Label>
                                <Select
                                    value={data.aktif}
                                    onValueChange={(value) =>
                                        setData(
                                            'aktif',
                                            value === '__all__' ? '' : value,
                                        )
                                    }
                                >
                                    <SelectTrigger className="h-9">
                                        <SelectValue placeholder="Semua" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__all__">
                                            Semua status
                                        </SelectItem>
                                        <SelectItem value="1">Aktif</SelectItem>
                                        <SelectItem value="0">
                                            Nonaktif
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex items-end gap-2 md:col-span-4">
                                <Button
                                    type="button"
                                    onClick={submitFilters}
                                    disabled={processing}
                                    className="flex-1"
                                >
                                    Apply Filters
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={clearAllFilters}
                                    disabled={isClearing}
                                >
                                    {isClearing ? 'Menghapus...' : 'Reset'}
                                </Button>
                            </div>
                        </div>
                    )}
                </div>

                {/* Table View */}
                {viewMode === 'table' && groupMode === 'list' && (
                    <div className="rounded-lg border bg-card">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="px-4 py-3 text-left">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    handleSort('sku')
                                                }
                                                className="flex items-center gap-1 font-medium hover:text-primary"
                                            >
                                                SKU
                                                <SortIcon field="sku" />
                                            </button>
                                        </th>
                                        <th className="px-4 py-3 text-left">
                                            Gambar
                                        </th>
                                        <th className="px-4 py-3 text-left">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    handleSort('nama')
                                                }
                                                className="flex items-center gap-1 font-medium hover:text-primary"
                                            >
                                                Nama
                                                <SortIcon field="nama" />
                                            </button>
                                        </th>
                                        <th className="px-4 py-3 text-left">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    handleSort('kategori')
                                                }
                                                className="flex items-center gap-1 font-medium hover:text-primary"
                                            >
                                                Kategori
                                                <SortIcon field="kategori" />
                                            </button>
                                        </th>
                                        <th className="px-4 py-3 text-right">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    handleSort('harga_jual')
                                                }
                                                className="ml-auto flex items-center gap-1 font-medium hover:text-primary"
                                            >
                                                Harga
                                                <SortIcon field="harga_jual" />
                                            </button>
                                        </th>
                                        <th className="px-4 py-3 text-center">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    handleSort('stok')
                                                }
                                                className="mx-auto flex items-center gap-1 font-medium hover:text-primary"
                                            >
                                                Stok
                                                <SortIcon field="stok" />
                                            </button>
                                        </th>
                                        <th className="px-4 py-3 text-left">
                                            <button
                                                type="button"
                                                className="flex items-center gap-1 font-medium hover:text-primary"
                                            >
                                                <Building2 className="h-3 w-3 mr-1" />
                                                Cabang
                                            </button>
                                        </th>
                                        <th className="px-4 py-3 text-center">
                                            Status
                                        </th>
                                        <th className="px-4 py-3 text-center">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(isLoading || processing) && (
                                        <SkeletonProductTable
                                            count={
                                                produks?.per_page ?? perPage
                                            }
                                        />
                                    )}
                                    {!isLoading &&
                                        !processing &&
                                        filteredData.map((p) => {
                                            const stok =
                                                p.stok_etalase?.[0]?.jumlah;
                                            const stockStatus =
                                                getStockStatus(stok);
                                            const imageUrl = getImageUrl(
                                                p.image_path,
                                            );

                                            return (
                                                <tr
                                                    key={p.id}
                                                    className="border-b last:border-0 hover:bg-muted/50"
                                                >
                                                <td className="px-4 py-3">
                                                    <code className="text-xs">
                                                        {p.sku}
                                                    </code>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <LazyImage
                                                        src={imageUrl}
                                                        alt={p.nama}
                                                        className="h-10 w-10"
                                                    />
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="font-medium">
                                                        {p.varian
                                                            ? `${p.varian} ${p.kelompok_nama || p.nama}`
                                                            : p.nama}
                                                    </div>
                                                    {p.kelompok_nama &&
                                                        p.kelompok_nama !==
                                                            p.nama && (
                                                            <div className="text-xs text-muted-foreground">
                                                                {
                                                                    p.kelompok_nama
                                                                }
                                                            </div>
                                                        )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge
                                                        variant="outline"
                                                        className="text-xs"
                                                    >
                                                        {p.tipe === 'beans'
                                                            ? 'Beans'
                                                            : p.tipe ===
                                                                'minuman'
                                                              ? 'Minuman'
                                                              : p.tipe === 'makanan'
                                                                ? 'Makanan'
                                                                : 'Snack'}
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
                                                    <Badge
                                                        variant="secondary"
                                                        className={cn(
                                                            'text-xs',
                                                            stockStatus.color,
                                                        )}
                                                    >
                                                        {stockStatus.icon}{' '}
                                                        {stok ?? '-'}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">
                                                    {p.cabang ? (
                                                        <Badge
                                                            variant="outline"
                                                            className="text-xs bg-blue-50 border-blue-200 text-blue-700"
                                                        >
                                                            <Building2 className="h-3 w-3 mr-1" />
                                                            {p.cabang.nama}
                                                        </Badge>
                                                    ) : (
                                                        <Badge
                                                            variant="secondary"
                                                            className="text-xs"
                                                        >
                                                            Legacy
                                                        </Badge>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-center">
                                                    <ToggleStatusBadge
                                                        produk={{
                                                            id: p.id,
                                                            nama: p.nama,
                                                            aktif: p.aktif,
                                                        }}
                                                        canManage={
                                                            canManageProduk
                                                        }
                                                        isToggling={
                                                            togglingId === p.id
                                                        }
                                                        onToggle={() =>
                                                            handleToggleStatus(
                                                                p,
                                                            )
                                                        }
                                                    />
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center justify-center gap-2">
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    asChild
                                                                    variant="ghost"
                                                                    size="icon"
                                                                >
                                                                    <Link
                                                                        href={`/produk/${p.id}`}
                                                                        aria-label="Lihat Detail"
                                                                    >
                                                                        <Eye
                                                                            className="h-5 w-5"
                                                                            aria-hidden="true"
                                                                        />
                                                                    </Link>
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                Lihat Detail
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        {canManageProduk && (
                                                            <Tooltip>
                                                                <TooltipTrigger asChild>
                                                                    <Button
                                                                        asChild
                                                                        variant="ghost"
                                                                        size="icon"
                                                                    >
                                                                        <Link
                                                                            href={`/produk/${p.id}/edit`}
                                                                            aria-label="Edit Produk"
                                                                        >
                                                                            <Pencil
                                                                                className="h-5 w-5"
                                                                                aria-hidden="true"
                                                                            />
                                                                        </Link>
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    Edit Produk
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        )}
                                                        {canDeleteProduk && (
                                                            <Tooltip>
                                                                <TooltipTrigger asChild>
                                                                    <Button
                                                                        type="button"
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        className="text-destructive hover:bg-destructive/10 hover:text-destructive focus-visible:ring-destructive/30"
                                                                        aria-label="Hapus Produk"
                                                                        onClick={() =>
                                                                            handleDeleteProduk(
                                                                                p,
                                                                            )
                                                                        }
                                                                    >
                                                                        <Trash2
                                                                            className="h-5 w-5"
                                                                            aria-hidden="true"
                                                                        />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    Hapus Produk
                                                                </TooltipContent>
                                                            </Tooltip>
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
                                <p className="text-lg font-medium">
                                    Tidak ada produk ditemukan
                                </p>
                                <p className="text-sm">
                                    Coba ubah filter atau tambah produk baru
                                </p>
                            </div>
                        )}
                    </div>
                )}

                {/* Grouped View */}
                {viewMode === 'table' &&
                    groupMode === 'grouped' &&
                    groupedProducts && (
                        <div className="space-y-4 rounded-lg border bg-card p-4">
                            {Object.entries(groupedProducts).map(
                                ([key, items]) => {
                                    const firstItem = items[0];
                                    const kelompokNama =
                                        firstItem.kelompok_nama ||
                                        firstItem.nama;
                                    const kategoriNama =
                                        firstItem.kategori?.nama ||
                                        'Tanpa Kategori';

                                    return (
                                        <div
                                            key={key}
                                            className="rounded-lg border-2 border-primary/20 bg-primary/5 p-4"
                                        >
                                            <div className="mb-3 flex items-center justify-between">
                                                <div className="flex items-center gap-3">
                                                    <Package className="h-6 w-6 text-primary" />
                                                    <div>
                                                        <h3 className="text-lg font-bold">
                                                            {kelompokNama}
                                                        </h3>
                                                        <p className="text-sm text-muted-foreground">
                                                            Kategori:{' '}
                                                            {kategoriNama} •{' '}
                                                            {
                                                                items.length
                                                            }{' '}
                                                            varian
                                                        </p>
                                                    </div>
                                                </div>
                                                {canManageProduk && (
                                                    <Link
                                                        href={`/produk/create?kelompok=${encodeURIComponent(
                                                            kelompokNama,
                                                        )}`}
                                                    >
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                        >
                                                            + Tambah Varian
                                                        </Button>
                                                    </Link>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                {items.map((produk, idx) => {
                                                    const isLast =
                                                        idx ===
                                                        items.length - 1;
                                                    const icon =
                                                        produk.varian === 'Hot'
                                                            ? (
                                                                <Flame className="h-4 w-4 text-orange-500" />
                                                            )
                                                            : produk.varian ===
                                                                'Ice'
                                                              ? (
                                                                  <Snowflake className="h-4 w-4 text-blue-500" />
                                                              )
                                                              : (
                                                                  <Package className="h-4 w-4 text-muted-foreground" />
                                                              );
                                                    const stok =
                                                        produk.stok_etalase?.[0]
                                                            ?.jumlah;
                                                    const imageUrl =
                                                        getImageUrl(
                                                            produk.image_path,
                                                        );

                                                    return (
                                                        <div
                                                            key={produk.id}
                                                            className="flex items-center gap-4 rounded-md border bg-card p-3 hover:bg-muted/50"
                                                        >
                                                            <div className="flex items-center gap-2 text-muted-foreground">
                                                                <span>
                                                                    {isLast
                                                                        ? '└─'
                                                                        : '├─'}
                                                                </span>
                                                                <span>{icon}</span>
                                                            </div>

                                                            <LazyImage
                                                                src={imageUrl}
                                                                alt={
                                                                    produk.nama
                                                                }
                                                                className="h-12 w-12"
                                                            />

                                                            <div className="flex-1">
                                                                <div className="font-medium">
                                                                    {produk.varian ||
                                                                        'Default'}{' '}
                                                                    -{' '}
                                                                    {formatHarga(
                                                                        produk.harga_jual,
                                                                    )}
                                                                </div>
                                                                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                                    <code>
                                                                        {
                                                                            produk.sku
                                                                        }
                                                                    </code>
                                                                    <span>
                                                                        •
                                                                    </span>
                                                                    <span>
                                                                        Stok:{' '}
                                                                        {stok ??
                                                                            '-'}
                                                                    </span>
                                                                    <span>
                                                                        •
                                                                    </span>
                                                                    <ToggleStatusBadge
                                                                        produk={{
                                                                            id: produk.id,
                                                                            nama: produk.nama,
                                                                            aktif: produk.aktif,
                                                                        }}
                                                                        canManage={
                                                                            canManageProduk
                                                                        }
                                                                        isToggling={
                                                                            togglingId ===
                                                                            produk.id
                                                                        }
                                                                        onToggle={() =>
                                                                            handleToggleStatus(
                                                                                produk,
                                                                            )
                                                                        }
                                                                    />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    );
                                },
                            )}
                        </div>
                    )}

                {/* Card View */}
                {viewMode === 'card' && (
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {(isLoading || processing) && (
                            <SkeletonProductCard
                                count={produks?.per_page ?? perPage}
                            />
                        )}
                        {!isLoading &&
                            !processing &&
                            filteredData.map((p) => {
                                const stok = p.stok_etalase?.[0]?.jumlah;
                                const stockStatus = getStockStatus(stok);
                                const imageUrl = getImageUrl(p.image_path);
                                const isAktif = p.aktif;

                            return (
                                <div
                                    key={p.id}
                                    className="group rounded-lg border bg-card p-4 transition-shadow hover:shadow-md"
                                >
                                    <div className="flex gap-3">
                                        <LazyImage
                                            src={imageUrl}
                                            alt={p.nama}
                                            className="h-20 w-20"
                                        />
                                        <div className="flex-1 space-y-1">
                                            <h3 className="line-clamp-2 font-semibold">
                                                {p.varian
                                                    ? `${p.varian} ${p.kelompok_nama || p.nama}`
                                                    : p.nama}
                                            </h3>
                                            <div className="flex items-center gap-2">
                                                <Badge
                                                    variant="outline"
                                                    className="text-xs"
                                                >
                                                    {p.tipe === 'beans'
                                                        ? 'Beans'
                                                        : p.tipe === 'minuman'
                                                          ? 'Minuman'
                                                          : p.tipe === 'makanan'
                                                            ? 'Makanan'
                                                            : 'Snack'}
                                                </Badge>
                                                <Badge
                                                    variant="secondary"
                                                    className={cn(
                                                        'text-xs',
                                                        stockStatus.color,
                                                    )}
                                                >
                                                    {stockStatus.icon}{' '}
                                                    {stok ?? '-'}
                                                </Badge>
                                            </div>
                                            <p className="text-lg font-bold text-primary">
                                                {formatHarga(p.harga_jual)}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="mt-3 flex items-center justify-between border-t pt-3">
                                        <code className="text-xs text-muted-foreground">
                                            {p.sku}
                                        </code>
                                        <div className="flex items-center gap-2">
                                            <ToggleStatusBadge
                                                produk={{
                                                    id: p.id,
                                                    nama: p.nama,
                                                    aktif: isAktif,
                                                }}
                                                canManage={canManageProduk}
                                                isToggling={
                                                    togglingId === p.id
                                                }
                                                onToggle={() =>
                                                    handleToggleStatus(p)
                                                }
                                            />
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button
                                                        asChild
                                                        variant="ghost"
                                                        size="icon"
                                                    >
                                                        <Link
                                                            href={`/produk/${p.id}`}
                                                            aria-label="Lihat Detail"
                                                        >
                                                            <Eye
                                                                className="h-5 w-5"
                                                                aria-hidden="true"
                                                            />
                                                        </Link>
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    Lihat Detail
                                                </TooltipContent>
                                            </Tooltip>
                                            {canManageProduk && (
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Button
                                                            asChild
                                                            variant="ghost"
                                                            size="icon"
                                                        >
                                                            <Link
                                                                href={`/produk/${p.id}/edit`}
                                                                aria-label="Edit Produk"
                                                            >
                                                                <Pencil
                                                                    className="h-5 w-5"
                                                                    aria-hidden="true"
                                                                />
                                                            </Link>
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        Edit Produk
                                                    </TooltipContent>
                                                </Tooltip>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                        {(produks?.data ?? []).length === 0 && (
                            <div className="col-span-full py-12 text-center text-muted-foreground">
                                <p className="text-lg font-medium">
                                    Tidak ada produk ditemukan
                                </p>
                                <p className="text-sm">
                                    Coba ubah filter atau tambah produk baru
                                </p>
                            </div>
                        )}
                    </div>
                )}

                {/* Compact View */}
                {viewMode === 'compact' && (
                    <div className="space-y-2">
                        {(isLoading || processing) && (
                            <SkeletonProductCompact
                                count={produks?.per_page ?? perPage}
                            />
                        )}
                        {!isLoading &&
                            !processing &&
                            filteredData.map((p) => {
                                const stok = p.stok_etalase?.[0]?.jumlah;
                                const stockStatus = getStockStatus(stok);

                                return (
                                <div
                                    key={p.id}
                                    className="flex items-center justify-between rounded-lg border bg-card p-3 hover:bg-muted/50"
                                >
                                    <div className="flex items-center gap-3">
                                        <ToggleStatusBadge
                                            produk={{
                                                id: p.id,
                                                nama: p.nama,
                                                aktif: p.aktif,
                                            }}
                                            canManage={canManageProduk}
                                            isToggling={
                                                togglingId === p.id
                                            }
                                            onToggle={() =>
                                                handleToggleStatus(p)
                                            }
                                        />
                                        <div>
                                            <div className="font-medium">
                                                {p.varian
                                                    ? `${p.varian} ${p.kelompok_nama || p.nama}`
                                                    : p.nama}
                                            </div>
                                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                <code>{p.sku}</code>
                                                <span>•</span>
                                                <span>{p.tipe}</span>
                                                <span>•</span>
                                                <span
                                                    className={
                                                        stockStatus.color
                                                    }
                                                >
                                                    {stockStatus.icon}{' '}
                                                    {stok ?? '-'}{' '}
                                                    {p.satuan_dasar}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-4">
                                        <span className="font-semibold">
                                            {formatHarga(p.harga_jual)}
                                        </span>
                                        <div className="flex items-center gap-2">
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button
                                                        asChild
                                                        variant="ghost"
                                                        size="icon"
                                                    >
                                                        <Link
                                                            href={`/produk/${p.id}`}
                                                            aria-label="Lihat Detail"
                                                        >
                                                            <Eye
                                                                className="h-5 w-5"
                                                                aria-hidden="true"
                                                            />
                                                        </Link>
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    Lihat Detail
                                                </TooltipContent>
                                            </Tooltip>
                                            {canManageProduk && (
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Button
                                                            asChild
                                                            variant="ghost"
                                                            size="icon"
                                                        >
                                                            <Link
                                                                href={`/produk/${p.id}/edit`}
                                                                aria-label="Edit Produk"
                                                            >
                                                                <Pencil
                                                                    className="h-5 w-5"
                                                                    aria-hidden="true"
                                                                />
                                                            </Link>
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        Edit Produk
                                                    </TooltipContent>
                                                </Tooltip>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                        {(produks?.data ?? []).length === 0 && (
                            <div className="py-12 text-center text-muted-foreground">
                                <p className="text-lg font-medium">
                                    Tidak ada produk ditemukan
                                </p>
                                <p className="text-sm">
                                    Coba ubah filter atau tambah produk baru
                                </p>
                            </div>
                        )}
                    </div>
                )}

                {/* Pagination */}
                <div className="flex flex-col gap-3 rounded-lg border bg-card p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2">
                            <Label className="text-xs">Show:</Label>
                            <Select
                                value={String(perPage)}
                                onValueChange={handlePerPageChange}
                            >
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
                            Showing {produks?.from ?? 0}-{produks?.to ?? 0} of{' '}
                            {produks?.total ?? 0}
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!produks?.prev_page_url}
                            onClick={() => {
                                if (produks?.prev_page_url) {
                                    router.get(
                                        produks.prev_page_url,
                                        {},
                                        {
                                            preserveState: true,
                                            preserveScroll: false,
                                        },
                                    );
                                }
                            }}
                        >
                            Previous
                        </Button>
                        <div className="flex items-center gap-1 text-sm">
                            <span className="font-medium">
                                {produks?.current_page ?? 1}
                            </span>
                            <span className="text-muted-foreground">of</span>
                            <span className="font-medium">
                                {produks?.last_page ?? 1}
                            </span>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!produks?.next_page_url}
                            onClick={() => {
                                if (produks?.next_page_url) {
                                    router.get(
                                        produks.next_page_url,
                                        {},
                                        {
                                            preserveState: true,
                                            preserveScroll: false,
                                        },
                                    );
                                }
                            }}
                        >
                            Next
                        </Button>
                    </div>
                </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
