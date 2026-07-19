import React, { useMemo, useState } from 'react';
import { Head, Link } from '@inertiajs/react';

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
            <Head title={selectedCabang ? `Menu — ${selectedCabang.nama}` : 'Menu — Encity Company'} />
            <style>{`
                * {
                    box-sizing: border-box;
                }

                .kiosk {
                    min-height: 100vh;
                    background: #FAFAF9;
                    font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
                    color: #111827;
                    padding: 32px;
                }

                .kiosk__topbar {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 16px;
                    max-width: 1440px;
                    margin: 0 auto 24px;
                    flex-wrap: wrap;
                }

                .kiosk__brand {
                    display: flex;
                    align-items: baseline;
                    gap: 10px;
                }

                .kiosk__brand-name {
                    font-size: 1.15rem;
                    font-weight: 800;
                    letter-spacing: -0.01em;
                    color: #111827;
                }

                .kiosk__brand-outlet {
                    font-size: 0.85rem;
                    color: #6B7280;
                }

                .kiosk__cabang-select {
                    border-radius: 12px;
                    border: 1px solid #E5E7EB;
                    background: #ffffff;
                    padding: 0.55rem 0.85rem;
                    font-size: 0.85rem;
                    color: #374151;
                    font-family: inherit;
                }

                .kiosk__tabs {
                    display: flex;
                    gap: 6px;
                    overflow-x: auto;
                    max-width: 1440px;
                    margin: 0 auto 24px;
                    padding-bottom: 4px;
                    scrollbar-width: none;
                }

                .kiosk__tabs::-webkit-scrollbar {
                    display: none;
                }

                .kiosk__tab {
                    flex: 0 0 auto;
                    padding: 0.55rem 1.1rem;
                    border-radius: 999px;
                    border: 1px solid #E5E7EB;
                    background: #ffffff;
                    color: #4B5563;
                    font-size: 0.85rem;
                    font-weight: 600;
                    letter-spacing: 0.01em;
                    text-transform: lowercase;
                    cursor: pointer;
                    transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
                }

                .kiosk__tab:hover {
                    border-color: #BFDBFE;
                }

                .kiosk__tab--active {
                    background: #111827;
                    border-color: #111827;
                    color: #ffffff;
                }

                .kiosk__layout {
                    display: grid;
                    grid-template-columns: 1fr;
                    gap: 24px;
                    max-width: 1440px;
                    margin: 0 auto;
                }

                @media (min-width: 960px) {
                    .kiosk__layout {
                        grid-template-columns: 40fr 60fr;
                        align-items: start;
                    }
                }

                /* -------------------- LEFT: promo hero -------------------- */
                .kiosk__hero {
                    position: sticky;
                    top: 32px;
                    border-radius: 24px;
                    background: linear-gradient(160deg, #E3F1FC 0%, #CFE8F8 55%, #BFDFF5 100%);
                    border: 1px solid #E5E7EB;
                    padding: 32px 28px;
                    min-height: 520px;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    overflow: hidden;
                }

                .kiosk__hero-eyebrow {
                    display: inline-flex;
                    align-self: flex-start;
                    background: rgba(255, 255, 255, 0.7);
                    border: 1px solid rgba(255, 255, 255, 0.9);
                    color: #1E3A5F;
                    font-size: 0.7rem;
                    font-weight: 700;
                    letter-spacing: 0.08em;
                    text-transform: uppercase;
                    padding: 6px 12px;
                    border-radius: 999px;
                }

                .kiosk__hero-title {
                    font-size: 2rem;
                    line-height: 1.15;
                    font-weight: 800;
                    color: #0F2B47;
                    margin: 18px 0 8px;
                    letter-spacing: -0.02em;
                }

                .kiosk__hero-copy {
                    color: #33526C;
                    line-height: 1.6;
                    font-size: 0.95rem;
                    max-width: 34ch;
                }

                .kiosk__hero-art {
                    position: relative;
                    flex: 1;
                    margin: 24px -4px 0;
                    display: flex;
                    align-items: flex-end;
                    justify-content: center;
                    gap: 14px;
                }

                .kiosk__hero-bottle {
                    background: rgba(255, 255, 255, 0.55);
                    border: 1px solid rgba(255, 255, 255, 0.9);
                    border-radius: 16px 16px 10px 10px;
                    box-shadow: 0 18px 30px rgba(15, 43, 71, 0.12);
                }

                .kiosk__hero-bottle--tall { width: 64px; height: 168px; }
                .kiosk__hero-bottle--mid { width: 64px; height: 140px; }
                .kiosk__hero-bottle--short { width: 64px; height: 118px; }

                .kiosk__hero-pack {
                    position: absolute;
                    right: 6px;
                    bottom: 4px;
                    width: 128px;
                    height: 96px;
                    background: #ffffff;
                    border: 1px solid #E5E7EB;
                    border-radius: 14px;
                    box-shadow: 0 20px 34px rgba(15, 43, 71, 0.16);
                }

                .kiosk__hero-foot {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-top: 24px;
                    color: #1E3A5F;
                    font-size: 0.8rem;
                    font-weight: 600;
                }

                /* -------------------- LEFT: product detail (on select) -------------------- */
                .kiosk__hero--detail {
                    justify-content: flex-start;
                    padding: 24px;
                }

                .kiosk__detail-close {
                    align-self: flex-end;
                    width: 32px;
                    height: 32px;
                    border-radius: 50%;
                    border: 1px solid rgba(255, 255, 255, 0.9);
                    background: rgba(255, 255, 255, 0.7);
                    color: #1E3A5F;
                    font-size: 1.1rem;
                    line-height: 1;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .kiosk__detail-close:hover {
                    background: #ffffff;
                }

                .kiosk__detail-image {
                    width: 100%;
                    height: 280px;
                    object-fit: cover;
                    border-radius: 18px;
                    border: 1px solid rgba(255, 255, 255, 0.9);
                    box-shadow: 0 18px 30px rgba(15, 43, 71, 0.14);
                    margin-top: 16px;
                }

                .kiosk__detail-image--placeholder {
                    background: linear-gradient(135deg, #F1F5F9, #DCEEFA);
                }

                .kiosk__detail-body {
                    margin-top: 20px;
                }

                .kiosk__detail-title {
                    font-size: 1.6rem;
                    font-weight: 800;
                    color: #0F2B47;
                    letter-spacing: -0.01em;
                    margin: 14px 0 6px;
                    line-height: 1.2;
                }

                .kiosk__detail-price {
                    font-size: 1.15rem;
                    font-weight: 700;
                    color: #1E3A5F;
                    margin-bottom: 14px;
                }

                .kiosk__detail-desc {
                    color: #33526C;
                    line-height: 1.7;
                    font-size: 0.92rem;
                }

                /* -------------------- RIGHT: menu list -------------------- */
                .kiosk__menu {
                    background: #ffffff;
                    border: 1px solid #E5E7EB;
                    border-radius: 24px;
                    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
                    padding: 24px;
                }

                .kiosk__menu-header {
                    display: flex;
                    align-items: baseline;
                    justify-content: space-between;
                    margin-bottom: 16px;
                }

                .kiosk__menu-title {
                    font-size: 1.05rem;
                    font-weight: 700;
                    color: #111827;
                }

                .kiosk__menu-count {
                    font-size: 0.8rem;
                    color: #9CA3AF;
                }

                .kiosk__scroll {
                    max-height: 640px;
                    overflow-y: auto;
                    display: flex;
                    flex-direction: column;
                    gap: 12px;
                    padding-right: 4px;
                }

                .kiosk__row {
                    display: flex;
                    align-items: center;
                    gap: 16px;
                    width: 100%;
                    background: #ffffff;
                    border: 1px solid #E5E7EB;
                    border-radius: 16px;
                    padding: 12px 16px;
                    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
                    transition: box-shadow 0.15s ease, border-color 0.15s ease, background 0.15s ease;
                    font-family: inherit;
                    text-align: left;
                    cursor: pointer;
                }

                .kiosk__row:hover {
                    border-color: #BFDBFE;
                    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
                }

                .kiosk__row:focus-visible {
                    outline: 2px solid #60A5FA;
                    outline-offset: 2px;
                }

                .kiosk__row--active {
                    border-color: #1E3A5F;
                    background: #F0F7FF;
                    box-shadow: 0 8px 18px rgba(30, 58, 95, 0.1);
                }

                .kiosk__thumb {
                    flex: 0 0 auto;
                    width: 56px;
                    height: 56px;
                    border-radius: 16px;
                    object-fit: cover;
                    background: linear-gradient(135deg, #F1F5F9, #E2E8F0);
                    border: 1px solid #E5E7EB;
                }

                .kiosk__row-body {
                    flex: 1;
                    min-width: 0;
                }

                .kiosk__row-title {
                    font-weight: 700;
                    font-size: 0.95rem;
                    color: #111827;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .kiosk__row-desc {
                    font-size: 0.8rem;
                    color: #9CA3AF;
                    margin-top: 2px;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .kiosk__row-price {
                    flex: 0 0 auto;
                    font-size: 0.9rem;
                    font-weight: 700;
                    color: #111827;
                }

                .kiosk__empty {
                    padding: 48px 24px;
                    text-align: center;
                    color: #9CA3AF;
                    background: #F8FAFC;
                    border: 1px dashed #E5E7EB;
                    border-radius: 16px;
                }

                .kiosk__no-cabang {
                    max-width: 1440px;
                    margin: 0 auto;
                    background: #ffffff;
                    border: 1px solid #E5E7EB;
                    border-radius: 24px;
                    padding: 40px;
                    color: #4B5563;
                    line-height: 1.8;
                }

                .kiosk__branch-list {
                    max-width: 1440px;
                    margin: 32px auto 0;
                    display: flex;
                    flex-wrap: wrap;
                    gap: 10px;
                }

                .kiosk__branch-chip {
                    text-decoration: none;
                    font-size: 0.8rem;
                    font-weight: 600;
                    color: #374151;
                    background: #ffffff;
                    border: 1px solid #E5E7EB;
                    border-radius: 999px;
                    padding: 6px 14px;
                }

                .kiosk__branch-chip:hover {
                    border-color: #BFDBFE;
                    color: #1E3A5F;
                }
            `}</style>

            <div className="kiosk">
                <div className="kiosk__topbar">
                    <div className="kiosk__brand">
                        <span className="kiosk__brand-name">encity coffee</span>
                        {selectedCabang ? <span className="kiosk__brand-outlet">· {selectedCabang.nama}</span> : null}
                    </div>

                    <select
                        className="kiosk__cabang-select"
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

                {hasSelectedCabang ? (
                    <>
                        <nav className="kiosk__tabs">
                            <button
                                type="button"
                                className={`kiosk__tab${activeTab === 'all' ? ' kiosk__tab--active' : ''}`}
                                onClick={() => setActiveTab('all')}
                            >
                                semua
                            </button>
                            {categoryTabs.map((tab) => (
                                <button
                                    type="button"
                                    key={tab.slug}
                                    className={`kiosk__tab${activeTab === tab.slug ? ' kiosk__tab--active' : ''}`}
                                    onClick={() => setActiveTab(tab.slug)}
                                >
                                    {tab.nama.toLowerCase()}
                                </button>
                            ))}
                        </nav>

                        <div className="kiosk__layout">
                            {selectedProduct ? (
                                <aside className="kiosk__hero kiosk__hero--detail">
                                    <button
                                        type="button"
                                        className="kiosk__detail-close"
                                        onClick={() => setSelectedProduct(null)}
                                        aria-label="Tutup detail produk"
                                    >
                                        ×
                                    </button>

                                    {selectedProduct.image_url ? (
                                        <img
                                            className="kiosk__detail-image"
                                            src={selectedProduct.image_url}
                                            alt={selectedProduct.nama}
                                        />
                                    ) : (
                                        <div className="kiosk__detail-image kiosk__detail-image--placeholder" aria-hidden="true" />
                                    )}

                                    <div className="kiosk__detail-body">
                                        {selectedProduct.kategori ? (
                                            <span className="kiosk__hero-eyebrow">{selectedProduct.kategori.nama.toLowerCase()}</span>
                                        ) : null}
                                        <h1 className="kiosk__detail-title">{selectedProduct.nama}</h1>
                                        <div className="kiosk__detail-price">{formatRupiah(selectedProduct.harga_jual)}</div>
                                        <p className="kiosk__detail-desc">
                                            {selectedProduct.deskripsi || 'Deskripsi produk belum tersedia.'}
                                        </p>
                                    </div>
                                </aside>
                            ) : (
                                <aside className="kiosk__hero">
                                    <span className="kiosk__hero-eyebrow">di cabang ini</span>
                                    <div>
                                        <h1 className="kiosk__hero-title">
                                            Kopi segar,
                                            <br />
                                            disajikan tiap hari
                                        </h1>
                                        <p className="kiosk__hero-copy">
                                            Dari biji pilihan sampai minuman botolan siap bawa pulang — semua ada di
                                            satu menu. Pilih produk di sebelah kanan untuk melihat detailnya di sini.
                                        </p>
                                    </div>

                                    <div className="kiosk__hero-art">
                                        <div className="kiosk__hero-bottle kiosk__hero-bottle--short" />
                                        <div className="kiosk__hero-bottle kiosk__hero-bottle--tall" />
                                        <div className="kiosk__hero-bottle kiosk__hero-bottle--mid" />
                                        <div className="kiosk__hero-pack" />
                                    </div>

                                    <div className="kiosk__hero-foot">
                                        <span>{selectedCabang?.nama}</span>
                                        <span>{filteredProducts.length} produk</span>
                                    </div>
                                </aside>
                            )}

                            <section className="kiosk__menu">
                                <div className="kiosk__menu-header">
                                    <span className="kiosk__menu-title">menu</span>
                                    <span className="kiosk__menu-count">{filteredProducts.length} item</span>
                                </div>

                                {filteredProducts.length > 0 ? (
                                    <div className="kiosk__scroll">
                                        {filteredProducts.map((product) => (
                                            <button
                                                type="button"
                                                key={product.id}
                                                className={`kiosk__row${selectedProduct?.id === product.id ? ' kiosk__row--active' : ''}`}
                                                onClick={() => handleSelectProduct(product)}
                                            >
                                                {product.image_url ? (
                                                    <img
                                                        className="kiosk__thumb"
                                                        src={product.image_url}
                                                        alt={product.nama}
                                                    />
                                                ) : (
                                                    <div className="kiosk__thumb" aria-hidden="true" />
                                                )}
                                                <div className="kiosk__row-body">
                                                    <div className="kiosk__row-title">{product.nama}</div>
                                                    <div className="kiosk__row-desc">
                                                        {product.deskripsi || 'Deskripsi produk belum tersedia.'}
                                                    </div>
                                                </div>
                                                <div className="kiosk__row-price">{formatRupiah(product.harga_jual)}</div>
                                            </button>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="kiosk__empty">Tidak ada produk pada kategori ini.</div>
                                )}
                            </section>
                        </div>
                    </>
                ) : (
                    <div className="kiosk__no-cabang">
                        {namaCabang
                            ? 'Cabang tidak ditemukan. Pastikan nama cabang sudah benar atau pilih dari dropdown di atas.'
                            : 'Pilih cabang dari dropdown di atas untuk melihat menu.'}
                    </div>
                )}

                <div className="kiosk__branch-list">
                    {cabangList.map((cabang) => (
                        <Link
                            key={cabang.id}
                            className="kiosk__branch-chip"
                            href={`/menupercabang/${encodeURIComponent(cabang.nama)}`}
                        >
                            {cabang.nama}
                        </Link>
                    ))}
                </div>
            </div>
        </>
    );
}
