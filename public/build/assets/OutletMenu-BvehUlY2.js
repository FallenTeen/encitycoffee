import{c as Q,r as s,j as t,H as U,L as K}from"./app-IcO8aWJf.js";import{S as ee}from"./SafeImage-Dk29-qE2.js";/* empty css            */import"./useStorage-BAtZUo3K.js";function le(p){const e=Q.c(58),{cabang:i,categories:c,kategoriSlug:l}=p,O=s.useRef(null),V=s.useRef(null),A=s.useRef(null);let k;e[0]===Symbol.for("react.memo_cache_sentinel")?(k={open:!1,src:"",name:""},e[0]=k):k=e[0];const[o,G]=s.useState(k),[j,Z]=s.useState(!1);let z;e[1]===Symbol.for("react.memo_cache_sentinel")?(z=(a,r)=>{G({open:!0,src:a,name:r}),setTimeout(()=>Z(!0),10),document.body.style.overflow="hidden"},e[1]=z):z=e[1];const J=z;let N;e[2]===Symbol.for("react.memo_cache_sentinel")?(N=()=>{Z(!1),setTimeout(()=>{G({open:!1,src:"",name:""}),document.body.style.overflow=""},380)},e[2]=N):N=e[2];const _=N;let C,L;e[3]===Symbol.for("react.memo_cache_sentinel")?(C=()=>{const a=r=>{r.key==="Escape"&&_()};return window.addEventListener("keydown",a),()=>window.removeEventListener("keydown",a)},L=[_],e[3]=C,e[4]=L):(C=e[3],L=e[4]),s.useEffect(C,L);let S;e[5]!==c||e[6]!==l?(S=l?c.filter(a=>a.slug===l):c,e[5]=c,e[6]=l,e[7]=S):S=e[7];const X=S;let E,R;e[8]===Symbol.for("react.memo_cache_sentinel")?(E=()=>{[{el:O.current,delay:.1},{el:V.current,delay:.32},{el:A.current,delay:.5}].forEach(ae)},R=[],e[8]=E,e[9]=R):(E=e[8],R=e[9]),s.useEffect(E,R);const D=`Menu ${i.nama} — Encity Company`;let d;e[10]!==D?(d=t.jsx(U,{title:D}),e[10]=D,e[11]=d):d=e[11];let $;e[12]===Symbol.for("react.memo_cache_sentinel")?($=t.jsx("style",{children:`
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
            `}),e[12]=$):$=e[12];let Y,M;e[13]===Symbol.for("react.memo_cache_sentinel")?(Y=t.jsx("div",{className:"ec-frame"}),M=["tl","tr","bl","br"],e[13]=Y,e[14]=M):(Y=e[13],M=e[14]);let F;e[15]===Symbol.for("react.memo_cache_sentinel")?(F=M.map(te),e[15]=F):F=e[15];let P;e[16]===Symbol.for("react.memo_cache_sentinel")?(P=t.jsxs(K,{href:"/menu",className:"ec-back",children:[t.jsx("svg",{width:"14",height:"14",viewBox:"0 0 24 24",fill:"none",stroke:"currentColor",strokeWidth:"1.4",strokeLinecap:"round",strokeLinejoin:"round",children:t.jsx("path",{d:"M19 12H5M11 6l-6 6 6 6"})}),t.jsx("span",{children:"Pilih Cabang"})]}),e[16]=P):P=e[16];let T;e[17]===Symbol.for("react.memo_cache_sentinel")?(T=t.jsx("span",{className:"ec-sup",children:"Menu Kami"}),e[17]=T):T=e[17];let m;e[18]!==i.nama?(m=t.jsx("h1",{className:"ec-title",children:i.nama}),e[18]=i.nama,e[19]=m):m=e[19];let x;e[20]!==i.alamat?(x=t.jsx("p",{className:"ec-address",children:i.alamat}),e[20]=i.alamat,e[21]=x):x=e[21];let W;e[22]===Symbol.for("react.memo_cache_sentinel")?(W=t.jsxs("div",{className:"ec-ornament",children:[t.jsx("div",{className:"ec-ornament-line"}),t.jsx("div",{className:"ec-ornament-dot"}),t.jsx("div",{className:"ec-ornament-diamond"}),t.jsx("div",{className:"ec-ornament-dot"}),t.jsx("div",{className:"ec-ornament-line"})]}),e[22]=W):W=e[22];let g;e[23]!==m||e[24]!==x?(g=t.jsxs("div",{className:"ec-head",ref:O,children:[T,m,x,W]}),e[23]=m,e[24]=x,e[25]=g):g=e[25];const I=`/cabang/${i.kode}`,q=`ec-pill${l?"":" ec-pill--active"}`;let h;e[26]!==I||e[27]!==q?(h=t.jsx(K,{href:I,className:q,children:"Semua"}),e[26]=I,e[27]=q,e[28]=h):h=e[28];let f;if(e[29]!==i.kode||e[30]!==c||e[31]!==l){let a;e[33]!==i.kode||e[34]!==l?(a=r=>t.jsx(K,{href:`/cabang/${i.kode}/${r.slug}`,className:`ec-pill${l===r.slug?" ec-pill--active":""}`,children:r.nama},r.id),e[33]=i.kode,e[34]=l,e[35]=a):a=e[35],f=c.map(a),e[29]=i.kode,e[30]=c,e[31]=l,e[32]=f}else f=e[32];let b;e[36]!==h||e[37]!==f?(b=t.jsxs("div",{className:"ec-filter",ref:V,children:[h,f]}),e[36]=h,e[37]=f,e[38]=b):b=e[38];let B;e[39]===Symbol.for("react.memo_cache_sentinel")?(B={width:"100%"},e[39]=B):B=e[39];let u;if(e[40]!==X){let a;e[42]===Symbol.for("react.memo_cache_sentinel")?(a=r=>r.produk.length>0&&t.jsxs("div",{className:"ec-section",children:[t.jsxs("div",{className:"ec-section-head",children:[t.jsx("span",{className:"ec-section-title",children:r.nama}),t.jsx("div",{className:"ec-section-line"})]}),t.jsx("div",{className:"ec-products",children:r.produk.map(n=>t.jsxs("div",{className:"ec-product",children:[t.jsx("div",{className:"ec-product-accent"}),n.image_url&&t.jsxs("div",{className:"ec-product-media",children:[t.jsx(ee,{src:n.image_url,alt:n.nama,className:"ec-product-img",fallbackClassName:"ec-product-img",showIcon:!1}),t.jsx("div",{className:"ec-product-zoom",onClick:()=>J(n.image_url,n.nama),role:"button","aria-label":`Lihat gambar ${n.nama}`,children:t.jsxs("div",{className:"ec-product-zoom-icon",children:[t.jsxs("svg",{width:"11",height:"11",viewBox:"0 0 24 24",fill:"none",stroke:"currentColor",strokeWidth:"1.6",strokeLinecap:"round",strokeLinejoin:"round",children:[t.jsx("circle",{cx:"11",cy:"11",r:"8"}),t.jsx("line",{x1:"21",y1:"21",x2:"16.65",y2:"16.65"}),t.jsx("line",{x1:"11",y1:"8",x2:"11",y2:"14"}),t.jsx("line",{x1:"8",y1:"11",x2:"14",y2:"11"})]}),t.jsx("span",{children:"Lihat Foto"})]})})]}),t.jsxs("div",{className:"ec-product-body",children:[t.jsxs("div",{className:"ec-product-top",children:[t.jsx("h3",{className:"ec-product-name",children:n.nama}),t.jsxs("span",{className:"ec-product-price",children:["Rp ",Number(n.harga_jual).toLocaleString("id-ID")]})]}),n.deskripsi&&t.jsxs(t.Fragment,{children:[t.jsx("div",{className:"ec-product-divider"}),t.jsx("p",{className:"ec-product-desc",children:n.deskripsi})]})]})]},n.id))})]},r.id),e[42]=a):a=e[42],u=X.map(a),e[40]=X,e[41]=u}else u=e[41];let v;e[43]!==u?(v=t.jsx("div",{style:B,ref:A,children:u}),e[43]=u,e[44]=v):v=e[44];let y;e[45]!==g||e[46]!==b||e[47]!==v?(y=t.jsxs("div",{className:"ec-bg",children:[Y,F,t.jsxs("div",{className:"ec-content",children:[P,g,b,v]})]}),e[45]=g,e[46]=b,e[47]=v,e[48]=y):y=e[48];let w;e[49]!==j||e[50]!==o.name||e[51]!==o.open||e[52]!==o.src?(w=o.open&&t.jsxs("div",{className:"ec-lightbox",role:"dialog","aria-modal":"true","aria-label":`Preview: ${o.name}`,children:[t.jsx("div",{className:`ec-lightbox-backdrop ec-lightbox-backdrop--${j?"visible":"hidden"}`,onClick:_}),t.jsxs("div",{className:`ec-lightbox-panel ec-lightbox-panel--${j?"visible":"hidden"}`,children:[t.jsx("div",{className:"ec-lightbox-frame",children:t.jsx("img",{src:o.src,alt:o.name,className:"ec-lightbox-img"})}),t.jsx("p",{className:"ec-lightbox-caption",children:o.name})]}),t.jsx("button",{className:"ec-lightbox-close",onClick:_,"aria-label":"Tutup preview",children:t.jsxs("svg",{width:"14",height:"14",viewBox:"0 0 24 24",fill:"none",stroke:"currentColor",strokeWidth:"1.6",strokeLinecap:"round",strokeLinejoin:"round",children:[t.jsx("line",{x1:"18",y1:"6",x2:"6",y2:"18"}),t.jsx("line",{x1:"6",y1:"6",x2:"18",y2:"18"})]})})]}),e[49]=j,e[50]=o.name,e[51]=o.open,e[52]=o.src,e[53]=w):w=e[53];let H;return e[54]!==d||e[55]!==y||e[56]!==w?(H=t.jsxs(t.Fragment,{children:[d,$,y,w]}),e[54]=d,e[55]=y,e[56]=w,e[57]=H):H=e[57],H}function te(p){return t.jsxs("svg",{className:`ec-corner ec-corner--${p}`,viewBox:"0 0 60 60",fill:"none",xmlns:"http://www.w3.org/2000/svg",children:[t.jsx("path",{d:"M2 2 L2 22",stroke:"rgba(196,160,80,0.30)",strokeWidth:"1"}),t.jsx("path",{d:"M2 2 L22 2",stroke:"rgba(196,160,80,0.30)",strokeWidth:"1"}),t.jsx("path",{d:"M2 2 L10 10",stroke:"rgba(196,160,80,0.18)",strokeWidth:"0.75"}),t.jsx("circle",{cx:"2",cy:"2",r:"1.5",fill:"rgba(196,160,80,0.40)"})]},p)}function ae(p){const{el:e,delay:i}=p;e&&(e.style.opacity="0",e.style.transform="translateY(22px)",e.style.transition=`opacity 1s cubic-bezier(0.16,1,0.3,1) ${i}s, transform 1s cubic-bezier(0.16,1,0.3,1) ${i}s`,requestAnimationFrame(()=>setTimeout(()=>{e.style.opacity="1",e.style.transform="translateY(0)"},60)))}export{le as default};
