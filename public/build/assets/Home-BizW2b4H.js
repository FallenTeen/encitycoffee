import{c as E,r as C,j as t,H as R}from"./app-C8EqnUZ6.js";/* empty css            */function O(){const e=E.c(23),a=C.useRef(null),r=C.useRef(null),S=C.useRef(null);let i,o;e[0]===Symbol.for("react.memo_cache_sentinel")?(i=()=>{[{el:a.current,delay:.1},{el:r.current,delay:.36},{el:S.current,delay:.58}].forEach(M)},o=[],e[0]=i,e[1]=o):(i=e[0],o=e[1]),C.useEffect(i,o);let n,s;e[2]===Symbol.for("react.memo_cache_sentinel")?(n=t.jsx(R,{title:"Encity Company"}),s=t.jsx("style",{children:`
                @import url('https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@400;700&family=Cinzel:wght@400;600&family=Raleway:wght@200;300&display=swap');

                *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
                html, body { width: 100%; min-height: 100vh; }

                .ec-bg {
                    min-height: 100vh;
                    width: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: clamp(40px, 8vh, 80px) clamp(20px, 5vw, 60px);
                    background:
                        radial-gradient(ellipse 90% 55% at 50% -5%,  rgba(210,175,90,0.13) 0%, transparent 60%),
                        radial-gradient(ellipse 70% 45% at 15% 50%,  rgba(180,120,40,0.07) 0%, transparent 60%),
                        radial-gradient(ellipse 70% 45% at 85% 50%,  rgba(180,120,40,0.07) 0%, transparent 60%),
                        radial-gradient(ellipse 100% 60% at 50% 110%,rgba(120,80,20,0.12)  0%, transparent 60%),
                        linear-gradient(175deg, #2e2619 0%, #231d12 40%, #1c1710 70%, #26200f 100%);
                    position: relative;
                    overflow: hidden;
                }

                /* Noise */
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
                    position: absolute;
                    top: clamp(12px,2.5vw,22px); left: clamp(12px,2.5vw,22px);
                    right: clamp(12px,2.5vw,22px); bottom: clamp(12px,2.5vw,22px);
                    border: 1px solid rgba(210,175,90,0.10);
                    pointer-events: none;
                    z-index: 1;
                }

                /* Corner ornaments */
                .ec-corner {
                    position: absolute;
                    width: clamp(40px,6vw,70px);
                    height: clamp(40px,6vw,70px);
                    pointer-events: none;
                    z-index: 1;
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

                /* ── Heading ── */
                .ec-head {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 8px;
                    margin-bottom: clamp(48px,8vh,72px);
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
                    font-size: clamp(1.4rem,4.2vw,3.5rem);
                    letter-spacing: 0.12em;
                    color: transparent;
                    background: linear-gradient(180deg,#e8d08a 0%,#c4a050 35%,#a07830 65%,#c4a050 100%);
                    -webkit-background-clip: text;
                    background-clip: text;
                    text-align: center;
                    line-height: 1.1;
                    filter: drop-shadow(0 2px 18px rgba(196,160,80,0.22));
                }
                .ec-ornament {
                    display: flex;
                    align-items: center;
                    gap: clamp(10px,2vw,18px);
                    margin-top: 10px;
                    width: clamp(220px,45vw,480px);
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

                /* ── Logos ── */
                .ec-logos {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: clamp(32px,7vw,130px);
                    margin-bottom: clamp(48px,8vh,68px);
                    width: 100%;
                    perspective: 1200px;
                    flex-wrap: wrap;
                }

                /* Outer wrapper — handles the ZOOM (scale) with spring */
                .ec-logo-wrap {
                    position: relative;
                    width:  clamp(160px,22vw,280px);
                    height: clamp(160px,22vw,280px);
                    flex-shrink: 0;
                    cursor: pointer;

                    /*
                     * Spring-like zoom:
                     * Fast attack (0.18s) then gentle settle overshoot via cubic-bezier
                     * cubic-bezier(0.34, 1.56, 0.64, 1)  ← overshoot spring
                     */
                    transition:
                        transform 0.55s cubic-bezier(0.34, 1.56, 0.64, 1);
                    transform: scale(1);
                    will-change: transform;
                }
                .ec-logo-wrap:hover {
                    transform: scale(1.08);
                }

                /* Inner — handles the Y-axis FLIP with velocity feel */
                .ec-logo-flip {
                    width: 100%;
                    height: 100%;
                    position: relative;
                    transform-style: preserve-3d;
                    /*
                     * Flip velocity:
                     * cubic-bezier(0.25, 0.8, 0.25, 1) = accelerate → overshoot → land
                     * duration 0.72s so the momentum is visible
                     */
                    transition: transform 0.72s cubic-bezier(0.25, 0.8, 0.25, 1);
                    will-change: transform;
                }
                .ec-logo-wrap:hover .ec-logo-flip {
                    transform: rotateY(180deg);
                }

                .ec-logo-face {
                    position: absolute;
                    inset: 0;
                    border-radius: 50%;
                    backface-visibility: hidden;
                    -webkit-backface-visibility: hidden;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    overflow: hidden;
                }
                .ec-logo-face--back {
                    transform: rotateY(180deg);
                }
                .ec-logo-face img {
                    width: 100%;
                    height: 100%;
                    object-fit: contain;
                    display: block;
                    filter: drop-shadow(0 6px 28px rgba(196,160,80,0.14));
                    transition: filter 0.55s ease;
                }
                .ec-logo-wrap:hover .ec-logo-face img {
                    filter: drop-shadow(0 10px 40px rgba(196,160,80,0.38));
                }

                /* Rings — also scale with parent for free via CSS */
                .ec-ring {
                    position: absolute;
                    border-radius: 50%;
                    pointer-events: none;
                    z-index: 3;
                    will-change: transform, opacity;
                }
                .ec-ring--1 {
                    inset: -9px;
                    border: 1px solid rgba(196,160,80,0.14);
                    transition:
                        border-color 0.55s ease,
                        opacity      0.55s ease;
                }
                .ec-ring--2 {
                    inset: -20px;
                    border: 1px dashed rgba(196,160,80,0.07);
                    animation: spin-slow 22s linear infinite;
                    transition: opacity 0.55s ease;
                }
                .ec-logo-wrap:hover .ec-ring--1 {
                    border-color: rgba(196,160,80,0.42);
                }

                /* Glow disc that fades in on hover — appears behind logo */
                .ec-glow {
                    position: absolute;
                    inset: -5%;
                    border-radius: 50%;
                    background: radial-gradient(circle, rgba(196,160,80,0.14) 0%, transparent 70%);
                    opacity: 0;
                    z-index: 0;
                    transition: opacity 0.55s cubic-bezier(0.34,1.56,0.64,1);
                    pointer-events: none;
                }
                .ec-logo-wrap:hover .ec-glow {
                    opacity: 1;
                }

                /* Divider */
                .ec-divider { display:flex; flex-direction:column; align-items:center; gap:8px; flex-shrink:0; }
                .ec-divider-line {
                    width: 1px;
                    height: clamp(36px,5vh,60px);
                    background: linear-gradient(to bottom, transparent, rgba(196,160,80,0.30), transparent);
                }
                .ec-divider-dot {
                    width: 4px; height: 4px;
                    background: rgba(196,160,80,0.35);
                    border-radius: 50%;
                }

                /* ── Button ── */
                .ec-btn {
                    position: relative;
                    display: inline-flex;
                    align-items: center;
                    gap: 12px;
                    padding: clamp(12px,1.8vh,15px) clamp(32px,5vw,56px);
                    font-family: 'Cinzel', serif;
                    font-weight: 400;
                    font-size: clamp(0.62rem,1.1vw,0.76rem);
                    letter-spacing: 0.30em;
                    text-transform: uppercase;
                    color: #c4a050;
                    text-decoration: none;
                    border: 1px solid rgba(196,160,80,0.38);
                    background: rgba(196,160,80,0.04);
                    overflow: hidden;
                    transition:
                        color        0.45s ease,
                        border-color 0.45s ease,
                        transform    0.40s cubic-bezier(0.34,1.56,0.64,1);
                    transform: scale(1);
                }
                .ec-btn::before {
                    content: '';
                    position: absolute;
                    inset: 0;
                    background: linear-gradient(120deg, rgba(196,160,80,0.12) 0%, rgba(196,160,80,0.06) 100%);
                    transform: translateX(-101%);
                    transition: transform 0.50s cubic-bezier(0.22,1,0.36,1);
                }
                .ec-btn:hover::before  { transform: translateX(0); }
                .ec-btn:hover {
                    color: #e8cc84;
                    border-color: rgba(196,160,80,0.70);
                    transform: scale(1.035);
                }
                .ec-btn-arrow {
                    width: clamp(12px,1.5vw,15px);
                    height: clamp(12px,1.5vw,15px);
                    transition: transform 0.45s cubic-bezier(0.22,1,0.36,1);
                    flex-shrink: 0;
                }
                .ec-btn:hover .ec-btn-arrow { transform: translateX(5px); }

                /* ── Keyframes ── */
                @keyframes glow-pulse {
                    0%,100% { opacity:0.4; box-shadow:none; }
                    50%     { opacity:1;   box-shadow: 0 0 8px rgba(196,160,80,0.45); }
                }
                @keyframes spin-slow {
                    to { transform: rotate(360deg); }
                }

                /* ── Responsive ── */
                @media (max-width: 640px) {
                    .ec-divider { display: none; }
                    .ec-logos   { gap: 28px; }
                    .ec-logo-wrap {
                        width:  clamp(130px,40vw,175px);
                        height: clamp(130px,40vw,175px);
                    }
                    .ec-corner { display: none; }
                }
                @media (min-width:641px) and (max-width:900px) {
                    .ec-logo-wrap {
                        width:  clamp(160px,28vw,220px);
                        height: clamp(160px,28vw,220px);
                    }
                }
            `}),e[2]=n,e[3]=s):(n=e[2],s=e[3]);let l,c;e[4]===Symbol.for("react.memo_cache_sentinel")?(l=t.jsx("div",{className:"ec-frame"}),c=["tl","tr","bl","br"],e[4]=l,e[5]=c):(l=e[4],c=e[5]);let p;e[6]===Symbol.for("react.memo_cache_sentinel")?(p=c.map(L),e[6]=p):p=e[6];let m,d;e[7]===Symbol.for("react.memo_cache_sentinel")?(m=t.jsx("span",{className:"ec-sup",children:"Purwokerto · Est. 2020"}),d=t.jsx("h1",{className:"ec-title",children:"ENCITYCOMPANY"}),e[7]=m,e[8]=d):(m=e[7],d=e[8]);let g;e[9]===Symbol.for("react.memo_cache_sentinel")?(g=t.jsxs("div",{className:"ec-head",ref:a,children:[m,d,t.jsxs("div",{className:"ec-ornament",children:[t.jsx("div",{className:"ec-ornament-line"}),t.jsx("div",{className:"ec-ornament-dot"}),t.jsx("div",{className:"ec-ornament-diamond"}),t.jsx("div",{className:"ec-ornament-dot"}),t.jsx("div",{className:"ec-ornament-line"})]})]}),e[9]=g):g=e[9];let x,f,h;e[10]===Symbol.for("react.memo_cache_sentinel")?(x=t.jsx("div",{className:"ec-glow"}),f=t.jsx("div",{className:"ec-ring ec-ring--2"}),h=t.jsx("div",{className:"ec-ring ec-ring--1"}),e[10]=x,e[11]=f,e[12]=h):(x=e[10],f=e[11],h=e[12]);let b;e[13]===Symbol.for("react.memo_cache_sentinel")?(b=t.jsx("div",{className:"ec-logo-face ec-logo-face--front",children:t.jsx("img",{src:"/logo_encity_roastery_gunungan_1.png",alt:"Encity Roastery"})}),e[13]=b):b=e[13];let v;e[14]===Symbol.for("react.memo_cache_sentinel")?(v=t.jsxs("div",{className:"ec-logo-wrap",children:[x,f,h,t.jsxs("div",{className:"ec-logo-flip",children:[b,t.jsx("div",{className:"ec-logo-face ec-logo-face--back",children:t.jsx("img",{src:"/logo_encity_roastery_gunungan_1.png",alt:"Encity Roastery"})})]})]}),e[14]=v):v=e[14];let w;e[15]===Symbol.for("react.memo_cache_sentinel")?(w=t.jsxs("div",{className:"ec-divider",children:[t.jsx("div",{className:"ec-divider-line"}),t.jsx("div",{className:"ec-divider-dot"}),t.jsx("div",{className:"ec-divider-line"})]}),e[15]=w):w=e[15];let u,y,j;e[16]===Symbol.for("react.memo_cache_sentinel")?(u=t.jsx("div",{className:"ec-glow"}),y=t.jsx("div",{className:"ec-ring ec-ring--2"}),j=t.jsx("div",{className:"ec-ring ec-ring--1"}),e[16]=u,e[17]=y,e[18]=j):(u=e[16],y=e[17],j=e[18]);let _;e[19]===Symbol.for("react.memo_cache_sentinel")?(_=t.jsx("div",{className:"ec-logo-face ec-logo-face--front",children:t.jsx("img",{src:"/liliuba_hitam.png",alt:"Li Liu Ba Coffee"})}),e[19]=_):_=e[19];let k;e[20]===Symbol.for("react.memo_cache_sentinel")?(k=t.jsxs("div",{className:"ec-logos",ref:r,children:[v,w,t.jsxs("div",{className:"ec-logo-wrap",children:[u,y,j,t.jsxs("div",{className:"ec-logo-flip",children:[_,t.jsx("div",{className:"ec-logo-face ec-logo-face--back",children:t.jsx("img",{src:"/liliuba_hitam.png",alt:"Li Liu Ba Coffee"})})]})]})]}),e[20]=k):k=e[20];let N;e[21]===Symbol.for("react.memo_cache_sentinel")?(N=t.jsx("span",{children:"Explore Our Menu"}),e[21]=N):N=e[21];let z;return e[22]===Symbol.for("react.memo_cache_sentinel")?(z=t.jsxs(t.Fragment,{children:[n,s,t.jsxs("div",{className:"ec-bg",children:[l,p,t.jsxs("div",{className:"ec-content",children:[g,k,t.jsxs("a",{href:"/cabang/CBG-001",className:"ec-btn",ref:S,children:[N,t.jsx("svg",{className:"ec-btn-arrow",viewBox:"0 0 24 24",fill:"none",stroke:"currentColor",strokeWidth:"1.4",strokeLinecap:"round",strokeLinejoin:"round",children:t.jsx("path",{d:"M5 12h14M13 6l6 6-6 6"})})]})]})]})]}),e[22]=z):z=e[22],z}function L(e){return t.jsxs("svg",{className:`ec-corner ec-corner--${e}`,viewBox:"0 0 60 60",fill:"none",xmlns:"http://www.w3.org/2000/svg",children:[t.jsx("path",{d:"M2 2 L2 22",stroke:"rgba(196,160,80,0.30)",strokeWidth:"1"}),t.jsx("path",{d:"M2 2 L22 2",stroke:"rgba(196,160,80,0.30)",strokeWidth:"1"}),t.jsx("path",{d:"M2 2 L10 10",stroke:"rgba(196,160,80,0.18)",strokeWidth:"0.75"}),t.jsx("circle",{cx:"2",cy:"2",r:"1.5",fill:"rgba(196,160,80,0.40)"})]},e)}function M(e){const{el:a,delay:r}=e;a&&(a.style.opacity="0",a.style.transform="translateY(22px)",a.style.transition=`opacity 1s cubic-bezier(0.16,1,0.3,1) ${r}s, transform 1s cubic-bezier(0.16,1,0.3,1) ${r}s`,requestAnimationFrame(()=>setTimeout(()=>{a.style.opacity="1",a.style.transform="translateY(0)"},60)))}export{O as default};
