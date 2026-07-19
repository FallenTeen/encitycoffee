import React, { useEffect, useRef, useState, useCallback } from 'react';
import { Head, Link } from '@inertiajs/react';
import SafeImage from '@/components/SafeImage';

interface Category {
    id: number;
    nama: string;
    slug: string;
    produk: Product[];
}

interface Product {
    id: number;
    nama: string;
    harga_jual: number;
    deskripsi: string;
    image_url?: string | null;
}

interface Cabang {
    id: number;
    kode: string;
    nama: string;
    alamat: string;
}

interface OutletMenuProps {
    cabang: Cabang;
    categories: Category[];
    selectedCategory?: Category;
    kategoriSlug?: string;
}

interface LightboxState {
    open: boolean;
    src: string;
    name: string;
}

export default function OutletMenu({ cabang, categories, selectedCategory, kategoriSlug }: OutletMenuProps) {
    const headRef   = useRef<HTMLDivElement>(null);
    const filterRef = useRef<HTMLDivElement>(null);
    const bodyRef   = useRef<HTMLDivElement>(null);

    const [lightbox, setLightbox] = useState<LightboxState>({ open: false, src: '', name: '' });
    const [lbVisible, setLbVisible] = useState(false);

    const openLightbox = useCallback((src: string, name: string) => {
        setLightbox({ open: true, src, name });
        setTimeout(() => setLbVisible(true), 10);
        document.body.style.overflow = 'hidden';
    }, []);

    const closeLightbox = useCallback(() => {
        setLbVisible(false);
        setTimeout(() => {
            setLightbox({ open: false, src: '', name: '' });
            document.body.style.overflow = '';
        }, 380);
    }, []);

    useEffect(() => {
        const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') closeLightbox(); };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [closeLightbox]);

    const displayedCategories = kategoriSlug
        ? categories.filter(c => c.slug === kategoriSlug)
        : categories;

    useEffect(() => {
        const items = [
            { el: headRef.current,   delay: 0.10 },
            { el: filterRef.current, delay: 0.32 },
            { el: bodyRef.current,   delay: 0.50 },
        ];
        items.forEach(({ el, delay }) => {
            if (!el) return;
            el.style.opacity    = '0';
            el.style.transform  = 'translateY(22px)';
            el.style.transition = `opacity 1s cubic-bezier(0.16,1,0.3,1) ${delay}s, transform 1s cubic-bezier(0.16,1,0.3,1) ${delay}s`;
            requestAnimationFrame(() => setTimeout(() => {
                el.style.opacity   = '1';
                el.style.transform = 'translateY(0)';
            }, 60));
        });
    }, []);

    return (
        <>
            <Head title={`Menu ${cabang.nama} — Encity Company`} />
            <style>{`
                @import url('https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@400;700&family=Cinzel:wght@400;600&family=Raleway:wght@200;300;400&display=swap');

                *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
                html, body { width: 100%; min-height: 100vh; }

                .ec-bg {
                    min-height: 100vh;
                    width: 100%;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    padding: clamp(40px,8vh,80px) clamp(20px,5vw,60px) clamp(60px,10vh,100px);
                    background:
                        radial-gradient(ellipse 90% 55% at 50% -5%,  rgba(210,175,90,0.13) 0%, transparent 60%),
                        radial-gradient(ellipse 70% 45% at 15% 50%,  rgba(180,120,40,0.07) 0%, transparent 60%),
                        radial-gradient(ellipse 70% 45% at 85% 50%,  rgba(180,120,40,0.07) 0%, transparent 60%),
                        radial-gradient(ellipse 100% 60% at 50% 110%,rgba(120,80,20,0.12)  0%, transparent 60%),
                        linear-gradient(175deg, #2e2619 0%, #231d12 40%, #1c1710 70%, #26200f 100%);
                    position: relative;
                    overflow-x: hidden;
                }
                .ec-bg::before {
                    content: '';
                    position: fixed;
                    inset: 0;
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
                    opacity: 0.055;
                    pointer-events: none;
                    z-index: 0;
                }
                .ec-frame {
                    position: fixed;
                    top: clamp(12px,2.5vw,22px); left: clamp(12px,2.5vw,22px);
                    right: clamp(12px,2.5vw,22px); bottom: clamp(12px,2.5vw,22px);
                    border: 1px solid rgba(210,175,90,0.10);
                    pointer-events: none;
                    z-index: 1;
                }
                .ec-corner {
                    position: fixed;
                    width: clamp(40px,6vw,70px);
                    height: clamp(40px,6vw,70px);
                    pointer-events: none;
                    z-index: 2;
                }
                .ec-corner--tl { top: clamp(16px,3vw,28px);    left: clamp(16px,3vw,28px); }
                .ec-corner--tr { top: clamp(16px,3vw,28px);    right: clamp(16px,3vw,28px);  transform: scaleX(-1); }
                .ec-corner--bl { bottom: clamp(16px,3vw,28px); left: clamp(16px,3vw,28px);   transform: scaleY(-1); }
                .ec-corner--br { bottom: clamp(16px,3vw,28px); right: clamp(16px,3vw,28px);  transform: scale(-1); }

                .ec-content {
                    position: relative;
                    z-index: 2;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    width: 100%;
                    max-width: 1200px;
                }

                /* ── Back link ── */
                .ec-back {
                    align-self: flex-start;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    margin-bottom: clamp(32px,5vh,48px);
                    font-family: 'Cinzel', serif;
                    font-size: clamp(0.58rem,1vw,0.68rem);
                    letter-spacing: 0.28em;
                    text-transform: uppercase;
                    color: rgba(196,160,80,0.50);
                    text-decoration: none;
                    transition: color 0.35s ease;
                }
                .ec-back:hover { color: rgba(196,160,80,0.90); }
                .ec-back svg { transition: transform 0.35s cubic-bezier(0.22,1,0.36,1); }
                .ec-back:hover svg { transform: translateX(-4px); }

                /* ── Heading ── */
                .ec-head {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 8px;
                    margin-bottom: clamp(40px,7vh,64px);
                    text-align: center;
                }
                .ec-sup {
                    font-family: 'Raleway', sans-serif;
                    font-weight: 200;
                    letter-spacing: 0.45em;
                    font-size: clamp(0.55rem,1.2vw,0.7rem);
                    color: rgba(210,175,90,0.40);
                    text-transform: uppercase;
                }
                .ec-title {
                    font-family: 'Cinzel Decorative', serif;
                    font-weight: 400;
                    font-size: clamp(1.3rem,3.8vw,2.8rem);
                    letter-spacing: 0.10em;
                    color: transparent;
                    background: linear-gradient(180deg,#e8d08a 0%,#c4a050 35%,#a07830 65%,#c4a050 100%);
                    -webkit-background-clip: text;
                    background-clip: text;
                    line-height: 1.15;
                    filter: drop-shadow(0 2px 18px rgba(196,160,80,0.22));
                }
                .ec-address {
                    font-family: 'Raleway', sans-serif;
                    font-weight: 300;
                    letter-spacing: 0.20em;
                    font-size: clamp(0.60rem,1.1vw,0.72rem);
                    color: rgba(210,175,90,0.32);
                    margin-top: 4px;
                    text-transform: uppercase;
                }
                .ec-ornament {
                    display: flex;
                    align-items: center;
                    gap: clamp(10px,2vw,18px);
                    margin-top: 12px;
                    width: clamp(180px,40vw,420px);
                }
                .ec-ornament-line {
                    flex: 1; height: 1px;
                    background: linear-gradient(90deg,transparent,rgba(196,160,80,0.38),transparent);
                }
                .ec-ornament-diamond {
                    width: 6px; height: 6px;
                    border: 1px solid rgba(196,160,80,0.55);
                    transform: rotate(45deg);
                    flex-shrink: 0;
                    animation: glow-pulse 4s ease-in-out infinite;
                }
                .ec-ornament-dot {
                    width: 3px; height: 3px;
                    background: rgba(196,160,80,0.35);
                    border-radius: 50%;
                    flex-shrink: 0;
                }

                /* ── Filter pills ── */
                .ec-filter {
                    display: flex;
                    flex-wrap: wrap;
                    gap: clamp(8px,1.2vw,12px);
                    justify-content: center;
                    margin-bottom: clamp(48px,8vh,72px);
                    width: 100%;
                }
                .ec-pill {
                    font-family: 'Cinzel', serif;
                    font-size: clamp(0.52rem,0.9vw,0.63rem);
                    letter-spacing: 0.25em;
                    text-transform: uppercase;
                    text-decoration: none;
                    padding: clamp(7px,1.1vh,10px) clamp(18px,2.5vw,28px);
                    border: 1px solid rgba(196,160,80,0.25);
                    color: rgba(220,185,100,0.65);
                    background: transparent;
                    transition: color 0.35s ease, border-color 0.35s ease, background 0.35s ease;
                }
                .ec-pill:hover {
                    color: rgba(240,210,120,0.95);
                    border-color: rgba(196,160,80,0.55);
                }
                .ec-pill--active {
                    color: #f0d88a;
                    border-color: rgba(196,160,80,0.65);
                    background: rgba(196,160,80,0.10);
                }

                /* ── Category section ── */
                .ec-section {
                    width: 100%;
                    margin-bottom: clamp(52px,9vh,80px);
                }
                .ec-section-head {
                    display: flex;
                    align-items: center;
                    gap: clamp(14px,2vw,22px);
                    margin-bottom: clamp(24px,4vh,36px);
                }
                .ec-section-title {
                    font-family: 'Cinzel', serif;
                    font-weight: 600;
                    font-size: clamp(0.75rem,1.4vw,0.95rem);
                    letter-spacing: 0.22em;
                    text-transform: uppercase;
                    color: rgba(232,200,120,0.90);
                    flex-shrink: 0;
                }
                .ec-section-line {
                    flex: 1; height: 1px;
                    background: linear-gradient(90deg, rgba(196,160,80,0.22), transparent);
                }

                /* ── Product grid ── */
                .ec-products {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(clamp(240px,28vw,320px), 1fr));
                    gap: clamp(14px,2vw,22px);
                }

                /* ── Product card ── */
                .ec-product {
                    position: relative;
                    display: flex;
                    flex-direction: column;
                    border: 1px solid rgba(196,160,80,0.10);
                    background: linear-gradient(135deg, rgba(196,160,80,0.04) 0%, rgba(196,160,80,0.01) 100%);
                    overflow: hidden;
                    transition:
                        border-color 0.40s ease,
                        transform    0.45s cubic-bezier(0.34,1.56,0.64,1),
                        box-shadow   0.40s ease;
                    opacity: 0;
                    animation: card-in 0.8s cubic-bezier(0.16,1,0.3,1) forwards;
                }
                .ec-product::before {
                    content: '';
                    position: absolute;
                    inset: 0;
                    background: linear-gradient(120deg, rgba(196,160,80,0.08) 0%, rgba(196,160,80,0.02) 100%);
                    transform: translateX(-101%);
                    transition: transform 0.45s cubic-bezier(0.22,1,0.36,1);
                    z-index: 0;
                }
                .ec-product:hover::before { transform: translateX(0); }
                .ec-product:hover {
                    border-color: rgba(196,160,80,0.30);
                    transform: translateY(-3px);
                    box-shadow: 0 14px 40px rgba(0,0,0,0.30), 0 0 32px rgba(196,160,80,0.06);
                }
                .ec-product-accent {
                    position: absolute;
                    top: 0; left: 0;
                    width: clamp(20px,3vw,32px);
                    height: 1px;
                    background: linear-gradient(90deg, rgba(196,160,80,0.45), transparent);
                    transition: width 0.40s ease;
                    z-index: 1;
                }
                .ec-product:hover .ec-product-accent { width: 55%; }

                /* ── Product image ── */
                .ec-product-media {
                    position: relative;
                    width: 100%;
                    overflow: hidden;
                    background: rgba(0,0,0,0.40);
                    flex-shrink: 0;
                    /* no border-radius on purpose — full bleed top */
                }
                .ec-product-img {
                    width: 100%;
                    height: clamp(160px,24vh,200px);
                    object-fit: cover;
                    display: block;
                    transition: transform 0.55s cubic-bezier(0.22,1,0.36,1), filter 0.45s ease;
                    filter: brightness(0.88) saturate(0.90);
                }
                .ec-product:hover .ec-product-img {
                    transform: scale(1.04);
                    filter: brightness(0.72) saturate(0.75);
                }

                /* Zoom trigger overlay */
                .ec-product-zoom {
                    position: absolute;
                    inset: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    opacity: 0;
                    transition: opacity 0.35s ease;
                    cursor: zoom-in;
                    z-index: 2;
                }
                .ec-product:hover .ec-product-zoom { opacity: 1; }
                .ec-product-zoom-icon {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 6px;
                    padding: 9px 18px;
                    border: 1px solid rgba(196,160,80,0.55);
                    background: rgba(20,16,8,0.72);
                    backdrop-filter: blur(6px);
                    font-family: 'Cinzel', serif;
                    font-size: 0.55rem;
                    letter-spacing: 0.22em;
                    text-transform: uppercase;
                    color: rgba(232,200,120,0.90);
                    transform: translateY(6px);
                    transition: transform 0.35s cubic-bezier(0.22,1,0.36,1), border-color 0.3s;
                }
                .ec-product:hover .ec-product-zoom-icon {
                    transform: translateY(0);
                }
                .ec-product-zoom-icon:hover {
                    border-color: rgba(196,160,80,0.85);
                    color: #f0d88a;
                }

                /* Gold bottom edge on image */
                .ec-product-media::after {
                    content: '';
                    position: absolute;
                    bottom: 0; left: 0; right: 0;
                    height: 1px;
                    background: linear-gradient(90deg, transparent, rgba(196,160,80,0.25), transparent);
                }

                /* ── Product body (text area) ── */
                .ec-product-body {
                    position: relative;
                    z-index: 1;
                    display: flex;
                    flex-direction: column;
                    padding: clamp(18px,2.6vw,26px);
                    flex: 1;
                }
                .ec-product-top {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    gap: 12px;
                    margin-bottom: clamp(8px,1.2vh,12px);
                }
                .ec-product-name {
                    font-family: 'Cinzel', serif;
                    font-weight: 600;
                    font-size: clamp(0.78rem,1.4vw,0.92rem);
                    letter-spacing: 0.06em;
                    color: #f0d88a;
                    line-height: 1.35;
                    flex: 1;
                }
                .ec-product-price {
                    font-family: 'Raleway', sans-serif;
                    font-weight: 400;
                    font-size: clamp(0.72rem,1.2vw,0.84rem);
                    letter-spacing: 0.06em;
                    color: rgba(232,200,120,0.95);
                    white-space: nowrap;
                    flex-shrink: 0;
                }
                .ec-product-divider {
                    width: 100%;
                    height: 1px;
                    background: linear-gradient(90deg, rgba(196,160,80,0.12), transparent);
                    margin-bottom: clamp(8px,1.2vh,12px);
                }
                .ec-product-desc {
                    font-family: 'Raleway', sans-serif;
                    font-weight: 300;
                    font-size: clamp(0.66rem,1vw,0.76rem);
                    color: rgba(220,195,145,0.65);
                    line-height: 1.70;
                    letter-spacing: 0.03em;
                }

                .ec-product:nth-child(1)  { animation-delay: 0.56s; }
                .ec-product:nth-child(2)  { animation-delay: 0.63s; }
                .ec-product:nth-child(3)  { animation-delay: 0.70s; }
                .ec-product:nth-child(4)  { animation-delay: 0.77s; }
                .ec-product:nth-child(5)  { animation-delay: 0.84s; }
                .ec-product:nth-child(6)  { animation-delay: 0.91s; }
                .ec-product:nth-child(7)  { animation-delay: 0.98s; }
                .ec-product:nth-child(8)  { animation-delay: 1.05s; }
                .ec-product:nth-child(9)  { animation-delay: 1.12s; }
                .ec-product:nth-child(10) { animation-delay: 1.19s; }
                .ec-product:nth-child(11) { animation-delay: 1.26s; }
                .ec-product:nth-child(12) { animation-delay: 1.33s; }

                /* ── Lightbox ── */
                .ec-lightbox {
                    position: fixed;
                    inset: 0;
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: clamp(20px,4vw,60px);
                }
                .ec-lightbox-backdrop {
                    position: absolute;
                    inset: 0;
                    background: rgba(14,11,6,0.92);
                    backdrop-filter: blur(14px) saturate(0.6);
                    -webkit-backdrop-filter: blur(14px) saturate(0.6);
                    transition: opacity 0.38s ease;
                    cursor: zoom-out;
                }
                .ec-lightbox-backdrop--hidden { opacity: 0; }
                .ec-lightbox-backdrop--visible { opacity: 1; }

                .ec-lightbox-panel {
                    position: relative;
                    z-index: 1;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 16px;
                    max-width: min(88vw, 860px);
                    width: 100%;
                    transition: opacity 0.38s ease, transform 0.40s cubic-bezier(0.16,1,0.3,1);
                }
                .ec-lightbox-panel--hidden  { opacity: 0; transform: scale(0.93) translateY(16px); }
                .ec-lightbox-panel--visible { opacity: 1; transform: scale(1)    translateY(0);     }

                .ec-lightbox-frame {
                    position: relative;
                    width: 100%;
                    border: 1px solid rgba(196,160,80,0.22);
                    overflow: hidden;
                    box-shadow:
                        0 40px 100px rgba(0,0,0,0.60),
                        0 0 0 1px rgba(196,160,80,0.06),
                        inset 0 0 60px rgba(0,0,0,0.20);
                }
                /* Corner accents on lightbox */
                .ec-lightbox-frame::before,
                .ec-lightbox-frame::after {
                    content: '';
                    position: absolute;
                    z-index: 2;
                    pointer-events: none;
                }
                .ec-lightbox-frame::before {
                    top: 0; left: 0;
                    width: 40px; height: 1px;
                    background: linear-gradient(90deg, rgba(196,160,80,0.70), transparent);
                }
                .ec-lightbox-frame::after {
                    top: 0; left: 0;
                    width: 1px; height: 40px;
                    background: linear-gradient(180deg, rgba(196,160,80,0.70), transparent);
                }
                .ec-lightbox-img {
                    width: 100%;
                    max-height: 72vh;
                    object-fit: contain;
                    display: block;
                    background: rgba(20,16,8,0.90);
                }
                .ec-lightbox-caption {
                    font-family: 'Cinzel', serif;
                    font-size: clamp(0.6rem,1.1vw,0.72rem);
                    letter-spacing: 0.28em;
                    text-transform: uppercase;
                    color: rgba(196,160,80,0.45);
                    text-align: center;
                }
                .ec-lightbox-close {
                    position: absolute;
                    top: clamp(12px,2.5vw,20px);
                    right: clamp(12px,2.5vw,20px);
                    z-index: 10;
                    width: 36px; height: 36px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border: 1px solid rgba(196,160,80,0.28);
                    background: rgba(20,16,8,0.70);
                    backdrop-filter: blur(6px);
                    cursor: pointer;
                    color: rgba(196,160,80,0.60);
                    transition: color 0.3s, border-color 0.3s, background 0.3s;
                    padding: 0;
                }
                .ec-lightbox-close:hover {
                    color: #f0d88a;
                    border-color: rgba(196,160,80,0.60);
                    background: rgba(30,24,12,0.88);
                }

                @keyframes glow-pulse {
                    0%,100% { opacity:0.4; box-shadow:none; }
                    50%     { opacity:1;   box-shadow: 0 0 8px rgba(196,160,80,0.45); }
                }
                @keyframes card-in {
                    from { opacity:0; transform:translateY(16px); }
                    to   { opacity:1; transform:translateY(0);    }
                }

                @media (max-width: 640px) {
                    .ec-corner  { display: none; }
                    .ec-products { grid-template-columns: 1fr; }
                    .ec-lightbox-panel { max-width: 96vw; }
                }
            `}</style>

            <div className="ec-bg">
                <div className="ec-frame" />

                {(['tl','tr','bl','br'] as const).map(pos => (
                    <svg key={pos} className={`ec-corner ec-corner--${pos}`} viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2 2 L2 22"  stroke="rgba(196,160,80,0.30)" strokeWidth="1"/>
                        <path d="M2 2 L22 2"  stroke="rgba(196,160,80,0.30)" strokeWidth="1"/>
                        <path d="M2 2 L10 10" stroke="rgba(196,160,80,0.18)" strokeWidth="0.75"/>
                        <circle cx="2" cy="2" r="1.5" fill="rgba(196,160,80,0.40)"/>
                    </svg>
                ))}

                <div className="ec-content">

                    {/* Back */}
                    <Link href="/menu" className="ec-back">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M19 12H5M11 6l-6 6 6 6"/>
                        </svg>
                        <span>Pilih Cabang</span>
                    </Link>

                    {/* Heading */}
                    <div className="ec-head" ref={headRef}>
                        <span className="ec-sup">Menu Kami</span>
                        <h1 className="ec-title">{cabang.nama}</h1>
                        <p className="ec-address">{cabang.alamat}</p>
                        <div className="ec-ornament">
                            <div className="ec-ornament-line" />
                            <div className="ec-ornament-dot" />
                            <div className="ec-ornament-diamond" />
                            <div className="ec-ornament-dot" />
                            <div className="ec-ornament-line" />
                        </div>
                    </div>

                    {/* Category filter */}
                    <div className="ec-filter" ref={filterRef}>
                        <Link
                            href={`/cabang/${cabang.kode}`}
                            className={`ec-pill${!kategoriSlug ? ' ec-pill--active' : ''}`}
                        >
                            Semua
                        </Link>
                        {categories.map(cat => (
                            <Link
                                key={cat.id}
                                href={`/cabang/${cabang.kode}/${cat.slug}`}
                                className={`ec-pill${kategoriSlug === cat.slug ? ' ec-pill--active' : ''}`}
                            >
                                {cat.nama}
                            </Link>
                        ))}
                    </div>

                    {/* Products */}
                    <div style={{ width: '100%' }} ref={bodyRef}>
                        {displayedCategories.map(category =>
                            category.produk.length > 0 && (
                                <div key={category.id} className="ec-section">
                                    <div className="ec-section-head">
                                        <span className="ec-section-title">{category.nama}</span>
                                        <div className="ec-section-line" />
                                    </div>
                                    <div className="ec-products">
                                        {category.produk.map(product => (
                                            <div key={product.id} className="ec-product">
                                                <div className="ec-product-accent" />

                                                {/* Image — only if available */}
                                                {product.image_url && (
                                                    <div className="ec-product-media">
                                                        <SafeImage
                                                            src={product.image_url}
                                                            alt={product.nama}
                                                            className="ec-product-img"
                                                            fallbackClassName="ec-product-img"
                                                            showIcon={false}
                                                        />
                                                        {/* Zoom overlay */}
                                                        <div
                                                            className="ec-product-zoom"
                                                            onClick={() => openLightbox(product.image_url!, product.nama)}
                                                            role="button"
                                                            aria-label={`Lihat gambar ${product.nama}`}
                                                        >
                                                            <div className="ec-product-zoom-icon">
                                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round">
                                                                    <circle cx="11" cy="11" r="8"/>
                                                                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                                                    <line x1="11" y1="8" x2="11" y2="14"/>
                                                                    <line x1="8" y1="11" x2="14" y2="11"/>
                                                                </svg>
                                                                <span>Lihat Foto</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                )}

                                                {/* Text body */}
                                                <div className="ec-product-body">
                                                    <div className="ec-product-top">
                                                        <h3 className="ec-product-name">{product.nama}</h3>
                                                        <span className="ec-product-price">
                                                            Rp {Number(product.harga_jual).toLocaleString('id-ID')}
                                                        </span>
                                                    </div>
                                                    {product.deskripsi && (
                                                        <>
                                                            <div className="ec-product-divider" />
                                                            <p className="ec-product-desc">{product.deskripsi}</p>
                                                        </>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )
                        )}
                    </div>

                </div>
            </div>

            {/* ── Lightbox ── */}
            {lightbox.open && (
                <div className="ec-lightbox" role="dialog" aria-modal="true" aria-label={`Preview: ${lightbox.name}`}>
                    {/* Backdrop */}
                    <div
                        className={`ec-lightbox-backdrop ec-lightbox-backdrop--${lbVisible ? 'visible' : 'hidden'}`}
                        onClick={closeLightbox}
                    />

                    {/* Panel */}
                    <div className={`ec-lightbox-panel ec-lightbox-panel--${lbVisible ? 'visible' : 'hidden'}`}>
                        <div className="ec-lightbox-frame">
                            <img
                                src={lightbox.src}
                                alt={lightbox.name}
                                className="ec-lightbox-img"
                            />
                        </div>
                        <p className="ec-lightbox-caption">{lightbox.name}</p>
                    </div>

                    {/* Close button */}
                    <button
                        className="ec-lightbox-close"
                        onClick={closeLightbox}
                        aria-label="Tutup preview"
                    >
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            )}
        </>
    );
}