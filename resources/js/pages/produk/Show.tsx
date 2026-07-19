import { useMemo, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import SafeImage from '@/components/SafeImage';

interface Cabang {
    id: number;
    kode: string;
    nama: string;
    alamat: string;
    aktif: boolean;
}

interface Product {
    id: number;
    nama: string;
    deskripsi?: string | null;
    harga_jual: number | string;
    image_path?: string | null;
    image_url?: string | null;
    kategori?: {
        id: number;
        nama: string;
        slug: string;
    } | null;
}

interface ListMenuProps {
    cabangList: Cabang[];
    selectedCabang?: Cabang | null;
    namaCabang?: string | null;
    produkList?: Product[] | null;
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

export default function ListMenu({ cabangList, selectedCabang, namaCabang, produkList }: ListMenuProps) {
    const products = useMemo(() => produkList ?? [], [produkList]);
    const hasSelectedCabang = Boolean(selectedCabang);

    // Category tabs are derived entirely from the products already loaded from
    // the database — no extra request, and any new kategori_produk row shows
    // up automatically the next time it has a product attached to it.
    const categoryTabs = useMemo(() => {
        const seen = new Map<string, string>();
        products.forEach((product) => {
            if (product.kategori) {
                seen.set(product.kategori.slug, product.kategori.nama);
            }
        });
        return Array.from(seen.entries())
            .sort((a, b) => a[1].localeCompare(b[1]))
            .map(([slug, nama]) => ({ slug, nama }));
    }, [products]);

    const [activeTab, setActiveTab] = useState<string>('all');
    const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);

    const filteredProducts = useMemo(() => {
        if (activeTab === 'all') return products;
        return products.filter((product) => product.kategori?.slug === activeTab);
    }, [products, activeTab]);

    const handleSelectProduct = (product: Product) => {
        // Clicking the already-selected row again deselects it and brings
        // back the promo hero on the left.
        setSelectedProduct((current) => (current?.id === product.id ? null : product));
    };

    return (
        <>
            <Head title={selectedCabang ? `Menu - ${selectedCabang.nama}` : 'Menu - Encity Coffee'} />

            {/*
                Layout notes:
                - On md+ (kiosk / cashier display: desktop or tablet) the whole page is
                  pinned to the viewport height and never scrolls. The left "now viewing"
                  panel and the right menu panel share that exact height, so the left
                  panel physically cannot move. Only the product list inside the right
                  panel scrolls; the "menu" header + category tabs stay put above it.
                - Below md (a customer opening the link on their phone) everything reverts
                  to a normal, naturally scrolling single column, with the "now viewing"
                  panel sticking to the top so switching products doesn't require
                  scrolling back up.
            */}
            <div className="flex min-h-screen flex-col bg-neutral-50 font-sans text-gray-900 md:h-screen md:overflow-hidden">
                {/* Topbar — thin and sticky so it stays disposable/out of the way while scrolling */}
                <header className="sticky top-0 z-30 flex-none border-b border-gray-200 bg-white/95 px-3 py-1.5 backdrop-blur supports-[backdrop-filter]:bg-white/80 sm:px-4">
                    <div className="mx-auto flex max-w-[1440px] flex-wrap items-center justify-between gap-2">
                        <div className="flex items-baseline gap-1.5 truncate">
                            <span className="text-sm font-extrabold tracking-tight text-gray-900 sm:text-base">
                                Encity Coffee
                            </span>
                            {selectedCabang ? (
                                <span className="truncate text-xs text-gray-500 sm:text-sm">· {selectedCabang.nama}</span>
                            ) : null}
                        </div>

                        <select
                            className="rounded-lg border border-gray-200 bg-white px-2.5 py-1 font-sans text-xs text-gray-700 sm:px-3 sm:py-1.5 sm:text-sm"
                            value={namaCabang ?? ''}
                            onChange={(event) => {
                                const selectedNama = event.target.value;
                                if (!selectedNama) return;
                                window.location.href = `/menupercabang/${encodeURIComponent(selectedNama)}`;
                            }}
                        >
                            <option value="">pilih cabang</option>
                            {cabangList.map((cabang) => (
                                <option key={cabang.id} value={cabang.nama}>
                                    {cabang.nama}
                                </option>
                            ))}
                        </select>
                    </div>
                </header>

                {/* Main content */}
                <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-4 p-2 sm:p-3 md:min-h-0 md:grid md:grid-cols-[2fr_3fr] md:gap-5 md:overflow-hidden md:p-4">
                    {hasSelectedCabang ? (
                        <>
                            {/* Left panel — fixed to the row height on desktop/tablet, never shifts.
                                On mobile it sticks below the topbar instead. */}
                            {selectedProduct ? (
                                <aside className="sticky top-11 z-10 flex min-h-[320px] flex-col overflow-hidden rounded-2xl border border-gray-200 bg-gradient-to-br from-sky-100 via-sky-200 to-sky-300 p-4 shadow-lg sm:top-12 sm:p-5 md:static md:top-auto md:z-auto md:h-full md:min-h-0 md:shadow-none">
                                    <button
                                        type="button"
                                        className="flex h-7 w-7 self-end items-center justify-center rounded-full border border-white/90 bg-white/70 text-base leading-none text-sky-900 hover:bg-white"
                                        onClick={() => setSelectedProduct(null)}
                                        aria-label="Tutup detail produk"
                                    >
                                        ×
                                    </button>

                                    {selectedProduct.image_url ? (
                                        <SafeImage
                                            src={selectedProduct.image_url}
                                            alt={selectedProduct.nama}
                                            className="mt-3 h-40 w-full flex-none rounded-xl border border-white/90 object-cover shadow-lg sm:h-52 md:h-1/2 md:flex-1"
                                            fallbackClassName="mt-3 h-40 w-full flex-none rounded-xl border border-white/90 bg-gradient-to-br from-slate-100 to-sky-100 shadow-lg sm:h-52 md:h-1/2 md:flex-1"
                                            showIcon={false}
                                        />
                                    ) : (
                                        <div
                                            className="mt-3 h-40 w-full flex-none rounded-xl border border-white/90 bg-gradient-to-br from-slate-100 to-sky-100 shadow-lg sm:h-52 md:h-1/2 md:flex-1"
                                            aria-hidden="true"
                                        />
                                    )}

                                    <div className="mt-4 min-h-0 overflow-y-auto">
                                        {selectedProduct.kategori ? (
                                            <span className="inline-flex self-start rounded-full border border-white/90 bg-white/70 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-sky-900">
                                                {selectedProduct.kategori.nama.toLowerCase()}
                                            </span>
                                        ) : null}
                                        <h1 className="mb-1 mt-2.5 text-lg font-extrabold leading-tight tracking-tight text-sky-950 sm:text-xl">
                                            {selectedProduct.nama}
                                        </h1>
                                        <div className="mb-2.5 text-base font-bold text-sky-900">
                                            {formatRupiah(selectedProduct.harga_jual)}
                                        </div>
                                        <p className="text-sm leading-6 text-sky-800">
                                            {selectedProduct.deskripsi || 'Deskripsi produk belum tersedia.'}
                                        </p>
                                    </div>
                                </aside>
                            ) : (
                                <aside className="sticky top-11 z-10 flex min-h-[320px] flex-col justify-between overflow-hidden rounded-2xl border border-gray-200 bg-gradient-to-br from-sky-100 via-sky-200 to-sky-300 p-5 shadow-lg sm:top-12 sm:p-6 md:static md:top-auto md:z-auto md:h-full md:min-h-0 md:shadow-none">
                                    <span className="inline-flex self-start rounded-full border border-white/90 bg-white/70 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-sky-900">
                                        {selectedCabang?.nama || 'Encity Coffee'}
                                    </span>
                                    <div>
                                        <h1 className="mb-1.5 mt-3 text-xl font-extrabold leading-tight tracking-tight text-sky-950 sm:text-2xl md:text-[1.75rem]">
                                            Kopi segar,
                                            <br />
                                            disajikan tiap hari
                                        </h1>
                                        <p className="max-w-[34ch] text-sm leading-relaxed text-sky-800">
                                            Best Coffee in town
                                        </p>
                                    </div>

                                    <div className="relative -mx-1 mt-4 flex flex-1 items-end justify-center gap-2.5">
                                        <div className="h-20 w-12 rounded-t-xl rounded-b-md border border-white/90 bg-white/55 shadow-lg sm:h-24 sm:w-14" />
                                        <div className="h-32 w-12 rounded-t-xl rounded-b-md border border-white/90 bg-white/55 shadow-lg sm:h-36 sm:w-14" />
                                        <div className="h-26 w-12 rounded-t-xl rounded-b-md border border-white/90 bg-white/55 shadow-lg sm:h-28 sm:w-14" />
                                        <div className="absolute bottom-1 right-1 h-16 w-24 rounded-xl border border-gray-200 bg-white shadow-lg" />
                                    </div>

                                    <div className="mt-4 flex items-center justify-between text-sm font-semibold text-sky-900">
                                        <span className="truncate">{selectedCabang?.nama}</span>
                                        <span className="flex-none">{filteredProducts.length} produk</span>
                                    </div>
                                </aside>
                            )}

                            {/* Right panel — header (menu label + category tabs + count) stays fixed;
                                only the product list beneath it scrolls on desktop/tablet. */}
                            <section className="flex min-h-0 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5 md:h-full">
                                <div className="mb-3 flex flex-none flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-3">
                                    <span className="text-sm font-bold text-gray-900 sm:text-base">menu</span>

                                    <div className="flex flex-wrap items-center gap-2 sm:gap-3">
                                        <nav className="flex max-w-full gap-1.5 overflow-x-auto pb-0.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                            <button
                                                type="button"
                                                className={cn(
                                                    'flex-none rounded-full border px-3 py-1.5 text-xs font-semibold tracking-wide lowercase transition-colors sm:px-3.5 sm:text-sm',
                                                    activeTab === 'all'
                                                        ? 'border-gray-900 bg-gray-900 text-white'
                                                        : 'border-gray-200 bg-white text-gray-600 hover:border-sky-300',
                                                )}
                                                onClick={() => setActiveTab('all')}
                                            >
                                                semua
                                            </button>
                                            {categoryTabs.map((tab) => (
                                                <button
                                                    type="button"
                                                    key={tab.slug}
                                                    className={cn(
                                                        'flex-none rounded-full border px-3 py-1.5 text-xs font-semibold tracking-wide lowercase transition-colors sm:px-3.5 sm:text-sm',
                                                        activeTab === tab.slug
                                                            ? 'border-gray-900 bg-gray-900 text-white'
                                                            : 'border-gray-200 bg-white text-gray-600 hover:border-sky-300',
                                                    )}
                                                    onClick={() => setActiveTab(tab.slug)}
                                                >
                                                    {tab.nama.toLowerCase()}
                                                </button>
                                            ))}
                                        </nav>
                                        <span className="flex-none text-xs text-gray-400 sm:text-sm">
                                            {filteredProducts.length} item
                                        </span>
                                    </div>
                                </div>

                                {filteredProducts.length > 0 ? (
                                    <div className="flex flex-col gap-2.5 md:min-h-0 md:flex-1 md:overflow-y-auto md:pr-1">
                                        {filteredProducts.map((product) => (
                                            <button
                                                type="button"
                                                key={product.id}
                                                className={cn(
                                                    'flex w-full cursor-pointer items-center gap-3 rounded-xl border bg-white px-3 py-2.5 text-left shadow-sm transition-all hover:border-sky-300 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-400 sm:gap-4 sm:px-4 sm:py-3',
                                                    selectedProduct?.id === product.id
                                                        ? 'border-sky-900 bg-sky-50 shadow-md'
                                                        : 'border-gray-200',
                                                )}
                                                onClick={() => handleSelectProduct(product)}
                                            >
                                                {product.image_url ? (
                                                    <SafeImage
                                                        src={product.image_url}
                                                        alt={product.nama}
                                                        className="h-12 w-12 flex-none rounded-xl border border-gray-200 object-cover sm:h-14 sm:w-14"
                                                        fallbackClassName="h-12 w-12 flex-none rounded-xl border border-gray-200 bg-gradient-to-br from-slate-100 to-slate-200 sm:h-14 sm:w-14"
                                                        showIcon={false}
                                                    />
                                                ) : (
                                                    <div
                                                        className="h-12 w-12 flex-none rounded-xl border border-gray-200 bg-gradient-to-br from-slate-100 to-slate-200 sm:h-14 sm:w-14"
                                                        aria-hidden="true"
                                                    />
                                                )}
                                                <div className="min-w-0 flex-1">
                                                    <div className="truncate text-sm font-bold text-gray-900">
                                                        {product.nama}
                                                    </div>
                                                    <div className="mt-0.5 truncate text-xs text-gray-400">
                                                        {product.deskripsi || 'Deskripsi produk belum tersedia.'}
                                                    </div>
                                                </div>
                                                <div className="flex-none text-sm font-bold text-gray-900">
                                                    {formatRupiah(product.harga_jual)}
                                                </div>
                                            </button>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="rounded-xl border border-dashed border-gray-200 bg-slate-50 p-10 text-center text-sm text-gray-400">
                                        Tidak ada produk pada kategori ini.
                                    </div>
                                )}
                            </section>
                        </>
                    ) : (
                        <div className="col-span-2 rounded-2xl border border-gray-200 bg-white p-8 leading-7 text-gray-600">
                            {namaCabang
                                ? 'Cabang tidak ditemukan. Pastikan nama cabang sudah benar atau pilih dari dropdown di atas.'
                                : 'Pilih cabang dari dropdown di atas untuk melihat menu.'}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}