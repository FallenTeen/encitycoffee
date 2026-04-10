import React, { useEffect, useRef } from 'react';
import { Head, Link } from '@inertiajs/react';

interface Cabang {
    id: number;
    kode: string;
    nama: string;
    alamat: string;
    aktif: boolean;
}

interface MenuProps {
    cabangList: Cabang[];
}

export default function Menu({ cabangList }: MenuProps) {
    const headRef  = useRef<HTMLDivElement>(null);
    const gridRef  = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const items = [
            { el: headRef.current,  delay: 0.10 },
            { el: gridRef.current,  delay: 0.36 },
        ];
        items.forEach(({ el, delay }) => {
            if (!el) return;
            el.style.opacity   = '0';
            el.style.transform = 'translateY(22px)';
            el.style.transition = `opacity 1s cubic-bezier(0.16,1,0.3,1) ${delay}s, transform 1s cubic-bezier(0.16,1,0.3,1) ${delay}s`;
            requestAnimationFrame(() => setTimeout(() => {
                el.style.opacity   = '1';
                el.style.transform = 'translateY(0)';
            }, 60));
        });
    }, []);

    return (
        <>
            <Head title="Cabang Kami — Encity Company" />
            <style>{`
                @import url('https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@400;700&family=Cinzel:wght@400;600&family=Raleway:wght@200;300;400&display=swap');

                *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
                html, body { width: 100%; min-height: 100vh; }

                /* ── Background ── */
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
                    overflow: hidden;
                }

                /* Noise texture */
                .ec-bg::before {
                    content: '';
                    position: fixed;
                    inset: 0;
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
                    opacity: 0.055;
                    pointer-events: none;
                    z-index: 0;
                }

                /* Frame border */
                .ec-frame {
                    position: fixed;
                    top: clamp(12px,2.5vw,22px); left: clamp(12px,2.5vw,22px);
                    right: clamp(12px,2.5vw,22px); bottom: clamp(12px,2.5vw,22px);
                    border: 1px solid rgba(210,175,90,0.10);
                    pointer-events: none;
                    z-index: 1;
                }

                /* Corner ornaments */
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

                /* ── Content ── */
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
                    margin-bottom: clamp(36px,6vh,56px);
                    font-family: 'Cinzel', serif;
                    font-size: clamp(0.58rem,1vw,0.68rem);
                    letter-spacing: 0.28em;
                    text-transform: uppercase;
                    color: rgba(196,160,80,0.50);
                    text-decoration: none;
                    transition: color 0.35s ease;
                }
                .ec-back:hover { color: rgba(196,160,80,0.90); }
                .ec-back svg {
                    transition: transform 0.35s cubic-bezier(0.22,1,0.36,1);
                }
                .ec-back:hover svg { transform: translateX(-4px); }

                /* ── Heading ── */
                .ec-head {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 8px;
                    margin-bottom: clamp(48px,8vh,72px);
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
                    font-size: clamp(1.4rem,4.2vw,3rem);
                    letter-spacing: 0.12em;
                    color: transparent;
                    background: linear-gradient(180deg,#e8d08a 0%,#c4a050 35%,#a07830 65%,#c4a050 100%);
                    -webkit-background-clip: text;
                    background-clip: text;
                    line-height: 1.15;
                    filter: drop-shadow(0 2px 18px rgba(196,160,80,0.22));
                }
                .ec-ornament {
                    display: flex;
                    align-items: center;
                    gap: clamp(10px,2vw,18px);
                    margin-top: 10px;
                    width: clamp(180px,40vw,420px);
                }
                .ec-ornament-line {
                    flex: 1;
                    height: 1px;
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

                /* ── Grid ── */
                .ec-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(clamp(240px, 28vw, 320px), 1fr));
                    gap: clamp(16px,2.5vw,28px);
                    width: 100%;
                }

                /* ── Card ── */
                .ec-card {
                    position: relative;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    padding: clamp(24px,3.5vw,36px) clamp(24px,3.5vw,36px) clamp(20px,3vw,28px);
                    border: 1px solid rgba(196,160,80,0.12);
                    background:
                        linear-gradient(135deg, rgba(196,160,80,0.05) 0%, rgba(196,160,80,0.02) 100%);
                    text-decoration: none;
                    overflow: hidden;
                    transition:
                        border-color 0.45s ease,
                        transform    0.50s cubic-bezier(0.34,1.56,0.64,1),
                        box-shadow   0.45s ease;
                    transform: translateY(0);
                    will-change: transform;
                    /* Staggered entrance */
                    opacity: 0;
                    animation: card-in 0.8s cubic-bezier(0.16,1,0.3,1) forwards;
                }

                /* Stagger each card */
                .ec-card:nth-child(1)  { animation-delay: 0.42s; }
                .ec-card:nth-child(2)  { animation-delay: 0.52s; }
                .ec-card:nth-child(3)  { animation-delay: 0.62s; }
                .ec-card:nth-child(4)  { animation-delay: 0.72s; }
                .ec-card:nth-child(5)  { animation-delay: 0.82s; }
                .ec-card:nth-child(6)  { animation-delay: 0.92s; }

                .ec-card::before {
                    content: '';
                    position: absolute;
                    inset: 0;
                    background: linear-gradient(120deg, rgba(196,160,80,0.09) 0%, rgba(196,160,80,0.03) 100%);
                    transform: translateX(-101%);
                    transition: transform 0.50s cubic-bezier(0.22,1,0.36,1);
                }
                .ec-card:hover::before  { transform: translateX(0); }
                .ec-card:hover {
                    border-color: rgba(196,160,80,0.38);
                    transform: translateY(-4px);
                    box-shadow: 0 16px 48px rgba(0,0,0,0.35), 0 0 40px rgba(196,160,80,0.07);
                }

                /* Top-left accent line */
                .ec-card-accent {
                    position: absolute;
                    top: 0; left: 0;
                    width: clamp(28px,4vw,40px);
                    height: 1px;
                    background: linear-gradient(90deg, rgba(196,160,80,0.55), transparent);
                    transition: width 0.45s ease;
                }
                .ec-card:hover .ec-card-accent { width: 60%; }

                /* Card number */
                .ec-card-num {
                    font-family: 'Cinzel', serif;
                    font-size: clamp(0.50rem,0.9vw,0.62rem);
                    letter-spacing: 0.30em;
                    color: rgba(196,160,80,0.28);
                    margin-bottom: clamp(14px,2vh,20px);
                    position: relative;
                }

                /* Card body */
                .ec-card-name {
                    font-family: 'Cinzel', serif;
                    font-weight: 600;
                    font-size: clamp(0.85rem,1.6vw,1.05rem);
                    letter-spacing: 0.08em;
                    color: #c8a860;
                    margin-bottom: clamp(8px,1.2vh,12px);
                    line-height: 1.35;
                    position: relative;
                }
                .ec-card-address {
                    font-family: 'Raleway', sans-serif;
                    font-weight: 300;
                    font-size: clamp(0.70rem,1.1vw,0.80rem);
                    color: rgba(210,185,130,0.45);
                    line-height: 1.65;
                    letter-spacing: 0.04em;
                    position: relative;
                }

                /* Card footer CTA */
                .ec-card-cta {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    margin-top: clamp(18px,3vh,26px);
                    font-family: 'Cinzel', serif;
                    font-size: clamp(0.52rem,0.85vw,0.62rem);
                    letter-spacing: 0.28em;
                    text-transform: uppercase;
                    color: rgba(196,160,80,0.40);
                    transition: color 0.35s ease;
                    position: relative;
                }
                .ec-card-cta svg {
                    transition: transform 0.40s cubic-bezier(0.22,1,0.36,1);
                    flex-shrink: 0;
                }
                .ec-card:hover .ec-card-cta         { color: rgba(196,160,80,0.85); }
                .ec-card:hover .ec-card-cta svg      { transform: translateX(5px); }

                /* Divider line inside card */
                .ec-card-divider {
                    width: 100%;
                    height: 1px;
                    background: linear-gradient(90deg, rgba(196,160,80,0.15), transparent);
                    margin: clamp(14px,2vh,18px) 0 0;
                    position: relative;
                }

                /* ── Empty state ── */
                .ec-empty {
                    font-family: 'Raleway', sans-serif;
                    font-weight: 300;
                    letter-spacing: 0.15em;
                    color: rgba(196,160,80,0.30);
                    font-size: clamp(0.75rem,1.2vw,0.90rem);
                    text-align: center;
                    padding: clamp(40px,8vh,80px) 20px;
                }

                /* ── Keyframes ── */
                @keyframes glow-pulse {
                    0%,100% { opacity:0.4; box-shadow:none; }
                    50%     { opacity:1;   box-shadow: 0 0 8px rgba(196,160,80,0.45); }
                }
                @keyframes card-in {
                    from { opacity:0; transform:translateY(18px); }
                    to   { opacity:1; transform:translateY(0);    }
                }

                /* ── Responsive ── */
                @media (max-width: 640px) {
                    .ec-corner { display: none; }
                    .ec-grid   { grid-template-columns: 1fr; }
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

                    {/* Back navigation */}
                    <Link href="/" className="ec-back">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M19 12H5M11 6l-6 6 6 6"/>
                        </svg>
                        <span>Kembali</span>
                    </Link>

                    {/* Heading */}
                    <div className="ec-head" ref={headRef}>
                        <span className="ec-sup">Temukan Kami</span>
                        <h1 className="ec-title">Cabang Kami</h1>
                        <div className="ec-ornament">
                            <div className="ec-ornament-line" />
                            <div className="ec-ornament-dot" />
                            <div className="ec-ornament-diamond" />
                            <div className="ec-ornament-dot" />
                            <div className="ec-ornament-line" />
                        </div>
                    </div>

                    {/* Grid */}
                    <div className="ec-grid" ref={gridRef}>
                        {cabangList.length === 0 ? (
                            <p className="ec-empty">Belum ada cabang yang tersedia.</p>
                        ) : (
                            cabangList.map((cabang, i) => (
                                <Link key={cabang.id} href={`/cabang/${cabang.kode}`} className="ec-card">
                                    <div className="ec-card-accent" />

                                    <div>
                                        <div className="ec-card-num">
                                            {String(i + 1).padStart(2, '0')}
                                        </div>
                                        <h3 className="ec-card-name">{cabang.nama}</h3>
                                        <p className="ec-card-address">{cabang.alamat}</p>
                                    </div>

                                    <div>
                                        <div className="ec-card-divider" />
                                        <div className="ec-card-cta">
                                            <span>Lihat Menu</span>
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" strokeLinejoin="round">
                                                <path d="M5 12h14M13 6l6 6-6 6"/>
                                            </svg>
                                        </div>
                                    </div>
                                </Link>
                            ))
                        )}
                    </div>

                </div>
            </div>
        </>
    );
}