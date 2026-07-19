import{c as H,r as I,j as e,H as U,L as G}from"./app-Bgsi2Q7c.js";/* empty css            */function P(o){const n=Number(o??0);return Number.isNaN(n)?"Rp 0":n.toLocaleString("id-ID",{style:"currency",currency:"IDR",minimumFractionDigits:0,maximumFractionDigits:0})}function X(o){const n=H.c(46),{cabangList:l,selectedCabang:a,namaCabang:w,produkList:B}=o;let j;n[0]!==B?(j=B??[],n[0]=B,n[1]=j):j=n[1];const d=j,A=!!a;let y;if(n[2]!==d){const i=new Map;d.forEach(r=>{r.kategori&&i.set(r.kategori.slug,r.kategori.nama)}),y=Array.from(i.entries()).sort(Q).map(O),n[2]=d,n[3]=y}else y=n[3];const z=y,[t,R]=I.useState("all"),[s,T]=I.useState(null);let D;n:{if(t==="all"){D=d;break n}let i;if(n[4]!==t||n[5]!==d){let r;n[7]!==t?(r=M=>M.kategori?.slug===t,n[7]=t,n[8]=r):r=n[8],i=d.filter(r),n[4]=t,n[5]=d,n[6]=i}else i=n[6];D=i}const c=D;let v;n[9]===Symbol.for("react.memo_cache_sentinel")?(v=i=>{T(r=>r?.id===i.id?null:i)},n[9]=v):v=n[9];const $=v,S=a?`Menu — ${a.nama}`:"Menu — Encity Company";let k;n[10]!==S?(k=e.jsx(U,{title:S}),n[10]=S,n[11]=k):k=n[11];let E;n[12]===Symbol.for("react.memo_cache_sentinel")?(E=e.jsx("style",{children:`
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
            `}),n[12]=E):E=n[12];let N;n[13]===Symbol.for("react.memo_cache_sentinel")?(N=e.jsx("span",{className:"kiosk__brand-name",children:"encity coffee"}),n[13]=N):N=n[13];let p;n[14]!==a?(p=a?e.jsxs("span",{className:"kiosk__brand-outlet",children:["· ",a.nama]}):null,n[14]=a,n[15]=p):p=n[15];let m;n[16]!==p?(m=e.jsxs("div",{className:"kiosk__brand",children:[N,p]}),n[16]=p,n[17]=m):m=n[17];const L=w??"";let F;n[18]===Symbol.for("react.memo_cache_sentinel")?(F=e.jsx("option",{value:"",children:"pilih cabang"}),n[18]=F):F=n[18];let x;n[19]!==l?(x=l.map(q),n[19]=l,n[20]=x):x=n[20];let _;n[21]!==L||n[22]!==x?(_=e.jsxs("select",{className:"kiosk__cabang-select",value:L,onChange:J,children:[F,x]}),n[21]=L,n[22]=x,n[23]=_):_=n[23];let h;n[24]!==m||n[25]!==_?(h=e.jsxs("div",{className:"kiosk__topbar",children:[m,_]}),n[24]=m,n[25]=_,n[26]=h):h=n[26];let f;n[27]!==t||n[28]!==z||n[29]!==c||n[30]!==A||n[31]!==w||n[32]!==a?.nama||n[33]!==s?(f=A?e.jsxs(e.Fragment,{children:[e.jsxs("nav",{className:"kiosk__tabs",children:[e.jsx("button",{type:"button",className:`kiosk__tab${t==="all"?" kiosk__tab--active":""}`,onClick:()=>R("all"),children:"semua"}),z.map(i=>e.jsx("button",{type:"button",className:`kiosk__tab${t===i.slug?" kiosk__tab--active":""}`,onClick:()=>R(i.slug),children:i.nama.toLowerCase()},i.slug))]}),e.jsxs("div",{className:"kiosk__layout",children:[s?e.jsxs("aside",{className:"kiosk__hero kiosk__hero--detail",children:[e.jsx("button",{type:"button",className:"kiosk__detail-close",onClick:()=>T(null),"aria-label":"Tutup detail produk",children:"×"}),s.image_url?e.jsx("img",{className:"kiosk__detail-image",src:s.image_url,alt:s.nama}):e.jsx("div",{className:"kiosk__detail-image kiosk__detail-image--placeholder","aria-hidden":"true"}),e.jsxs("div",{className:"kiosk__detail-body",children:[s.kategori?e.jsx("span",{className:"kiosk__hero-eyebrow",children:s.kategori.nama.toLowerCase()}):null,e.jsx("h1",{className:"kiosk__detail-title",children:s.nama}),e.jsx("div",{className:"kiosk__detail-price",children:P(s.harga_jual)}),e.jsx("p",{className:"kiosk__detail-desc",children:s.deskripsi||"Deskripsi produk belum tersedia."})]})]}):e.jsxs("aside",{className:"kiosk__hero",children:[e.jsx("span",{className:"kiosk__hero-eyebrow",children:"di cabang ini"}),e.jsxs("div",{children:[e.jsxs("h1",{className:"kiosk__hero-title",children:["Kopi segar,",e.jsx("br",{}),"disajikan tiap hari"]}),e.jsx("p",{className:"kiosk__hero-copy",children:"Dari biji pilihan sampai minuman botolan siap bawa pulang — semua ada di satu menu. Pilih produk di sebelah kanan untuk melihat detailnya di sini."})]}),e.jsxs("div",{className:"kiosk__hero-art",children:[e.jsx("div",{className:"kiosk__hero-bottle kiosk__hero-bottle--short"}),e.jsx("div",{className:"kiosk__hero-bottle kiosk__hero-bottle--tall"}),e.jsx("div",{className:"kiosk__hero-bottle kiosk__hero-bottle--mid"}),e.jsx("div",{className:"kiosk__hero-pack"})]}),e.jsxs("div",{className:"kiosk__hero-foot",children:[e.jsx("span",{children:a?.nama}),e.jsxs("span",{children:[c.length," produk"]})]})]}),e.jsxs("section",{className:"kiosk__menu",children:[e.jsxs("div",{className:"kiosk__menu-header",children:[e.jsx("span",{className:"kiosk__menu-title",children:"menu"}),e.jsxs("span",{className:"kiosk__menu-count",children:[c.length," item"]})]}),c.length>0?e.jsx("div",{className:"kiosk__scroll",children:c.map(i=>e.jsxs("button",{type:"button",className:`kiosk__row${s?.id===i.id?" kiosk__row--active":""}`,onClick:()=>$(i),children:[i.image_url?e.jsx("img",{className:"kiosk__thumb",src:i.image_url,alt:i.nama}):e.jsx("div",{className:"kiosk__thumb","aria-hidden":"true"}),e.jsxs("div",{className:"kiosk__row-body",children:[e.jsx("div",{className:"kiosk__row-title",children:i.nama}),e.jsx("div",{className:"kiosk__row-desc",children:i.deskripsi||"Deskripsi produk belum tersedia."})]}),e.jsx("div",{className:"kiosk__row-price",children:P(i.harga_jual)})]},i.id))}):e.jsx("div",{className:"kiosk__empty",children:"Tidak ada produk pada kategori ini."})]})]})]}):e.jsx("div",{className:"kiosk__no-cabang",children:w?"Cabang tidak ditemukan. Pastikan nama cabang sudah benar atau pilih dari dropdown di atas.":"Pilih cabang dari dropdown di atas untuk melihat menu."}),n[27]=t,n[28]=z,n[29]=c,n[30]=A,n[31]=w,n[32]=a?.nama,n[33]=s,n[34]=f):f=n[34];let g;n[35]!==l?(g=l.map(K),n[35]=l,n[36]=g):g=n[36];let b;n[37]!==g?(b=e.jsx("div",{className:"kiosk__branch-list",children:g}),n[37]=g,n[38]=b):b=n[38];let u;n[39]!==h||n[40]!==f||n[41]!==b?(u=e.jsxs("div",{className:"kiosk",children:[h,f,b]}),n[39]=h,n[40]=f,n[41]=b,n[42]=u):u=n[42];let C;return n[43]!==u||n[44]!==k?(C=e.jsxs(e.Fragment,{children:[k,E,u]}),n[43]=u,n[44]=k,n[45]=C):C=n[45],C}function K(o){return e.jsx(G,{className:"kiosk__branch-chip",href:`/menupercabang/${encodeURIComponent(o.nama)}`,children:o.nama},o.id)}function q(o){return e.jsx("option",{value:o.nama,children:o.nama},o.id)}function J(o){const n=o.target.value;n&&(window.location.href=`/menupercabang/${encodeURIComponent(n)}`)}function O(o){const[n,l]=o;return{slug:n,nama:l}}function Q(o,n){return o[1].localeCompare(n[1])}export{X as default};
