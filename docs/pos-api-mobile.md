**Dokumentasi API POS Mobile (Fokus: Alur & Simulasi)**

Dokumentasi ringkas ini memfokuskan kepada skenario penggunaan API dari aplikasi POS mobile: transaksi penjualan (membeli minuman), manajemen shift (login, ganti/tutup shift), serta pencatatan pendukung (kalibrasi, stok, sinkronisasi). Format: endpoint → urutan panggilan (simulasi) → request/response yang diharapkan.

**Catatan Umum**
- **Base URL**: `https://<server>/api` atau `http://localhost:8000/api` saat development.
- **Autentikasi**: bisa berbasis session (login form) atau token (mis. Sanctum). Semua request POS diasumsikan sudah ter-autentikasi.
- **Header Umum**: `Content-Type: application/json`, `Accept: application/json`.

**Ringkasan Endpoint Utama**
- Auth: `POST /pos/auth/login`, `POST /pos/auth/logout`, `GET /pos/auth/me`.
- Shift: `GET /pos/shift/aktif`, `POST /pos/shift` (buka), `POST /pos/shift/{shift}/tutup`, `GET /pos/shift` (riwayat).
- Produk: `GET /pos/produk` , `GET /pos/produk/{id}`.
- Transaksi: `POST /pos/transaksi`, `GET /pos/transaksi/{id}`, `POST /pos/transaksi/{id}/batal`.
- Kalibrasi: `POST /pos/kalibrasi`, `GET /pos/kalibrasi/{shift}`, `GET /pos/kalibrasi/last`.
- Stok/Riwayat: `GET /pos/stok`, `GET /pos/stok/low`, `GET /pos/mutasi`.
- Sinkronisasi: `POST /pos/sinkronisasi` (jadwalkan / kirim antrian).

**Skenario 1 — Membeli Minuman (Kasir di Mobile)**
Urutan panggilan (ideal):
1. (Opsional) Pastikan shift terbuka: `GET /pos/shift/aktif`.
   - Jika response `shift: null`, maka buka shift terlebih dahulu (langkah 2).
2. (Jika perlu) Buka shift: `POST /pos/shift`
   - Body: `{ "cabang_id": 1, "kas_awal": 500000 }`
   - Expect: 201 Created, body memuat `shift.id`.
3. Buat transaksi penjualan: `POST /pos/transaksi`
   - Body contoh:
     {
       "shift_id": 11,
       "user_id": 3,
       "items": [
         { "produk_id": 21, "qty": 1, "harga": 18000, "satuan_id": 1 },
         { "produk_id": 34, "qty": 2, "harga": 15000, "satuan_id": 1 }
       ],
       "pembayaran": { "method": "tunai", "jumlah": 48000 }
     }
   - Expect: 201 Created, response `{ "transaksi": { "id": 123, "invoice": "POS-...", "total": 48000 } }`.
4. (Opsional) Jika perlu tampilkan/print struk: `GET /pos/transaksi/{id}` → 200 OK dengan detail lengkap.
**Dokumentasi API POS Mobile (Fokus: Alur & Simulasi)**

Dokumen ini menambahkan pemfokusan dan simulasi step-by-step untuk kasus POS mobile: transaksi minuman (kasir), login & buka/ganti shift, dan pencatatan pendukung (kalibrasi, stok, sinkronisasi). Tujuan: memberikan alur panggilan API, contoh payload/response, serta efek samping yang diharapkan (mutasi stok, laporan shift).

**Catatan ringkas**
- Base URL: `https://<server>/api` atau `http://localhost:8000/api` pada development.
- Autentikasi: session or token (mis. Sanctum). Semua contoh diasumsikan user sudah ter-autentikasi.
- Header umum: `Content-Type: application/json`, `Accept: application/json`, `Authorization: Bearer <token>` bila token digunakan.

**Prinsip penting untuk mobile POS**
- Selalu cek shift aktif (`GET /pos/shift/aktif`) saat app dibuka.
- Gunakan `client_request_id` unik pada transaksi untuk idempotency dan deteksi duplikat saat sinkronisasi.
- Server harus merekam `MutasiStok` untuk setiap perubahan stok.

---

**Simulasi Lengkap: Membeli Minuman (Kasir Mobile)**
Tujuan: contoh alur end-to-end saat kasir menjual minuman single order.

Langkah & panggilan API:
1) Pastikan user login
   - POST `/pos/auth/login` (Body: `{ "email": "kasir@cabang.local", "password": "secret" }`)
   - Expect 200 + token/user

2) Cek shift aktif
   - GET `/pos/shift/aktif`
   - Response 200: `{ "shift": { "id": 11, "user_id": 3, "status": "open", ... } }` atau `{ "shift": null }`
   - Jika `null`: buka shift lanjut ke langkah 3

3) (Jika perlu) Buka shift baru
   - POST `/pos/shift`
   - Body contoh:
     {
       "cabang_id": 1,
       "user_id": 3,
       "saldo_awal": 500000,
       "client_request_id": "shift-open-20251122-3"
     }
   - Expect 201: `{ "shift": { "id": 11, "waktu_buka": "2025-11-22T06:30:00Z" } }`

4) Pilih produk & kalibrasi (jika produk berupa minuman yang perlu kalibrasi)
   - GET `/pos/produk?q=espresso` → pilih `produk_id` dan cek apakah `perlu_kalibrasi: true`
   - Jika perlu, ambil kalibrasi terakhir: `GET /pos/kalibrasi/last` untuk mendapatkan konversi gram → ml/shot

5) Buat transaksi penjualan
   - POST `/pos/transaksi`
   - Body contoh (curl):
     ```json
     {
       "client_request_id": "txn-20251122-0001",
       "shift_id": 11,
       "user_id": 3,
       "items": [
         { "produk_id": 101, "qty": 1, "harga": 18000, "satuan_id": 1, "kalibrasi_id": 55 },
         { "produk_id": 202, "qty": 2, "harga": 15000, "satuan_id": 1 }
       ],
       "pembayaran": [ { "metode": "tunai", "jumlah": 48000 } ],
       "catatan": "No sugar"
     }
     ```
   - Expect: 201 Created
     ```json
     {
       "transaksi": { "id": 12345, "nomor_invoice": "POS-20251122-12345", "total": 48000, "status": "selesai" }
     }
     ```

Efek samping yang diharapkan di server:
- Buat entri `Transaksi` & `TransaksiItem`.
- Kurangi stok: catat `MutasiStok` untuk tiap `produk_id` (menggunakan konversi kalibrasi bila diperlukan).
- Buat entri pembayaran dan laporkan metode pada `laporan_shift`.

6) Ambil detail transaksi untuk struk
   - GET `/pos/transaksi/{id}` → 200 OK dengan rincian untuk print

Penanganan error & idempotency:
- Jika server menerima `client_request_id` yang sama, kembalikan entri yang sudah dibuat (201/200) — jangan buat duplikat.
- 422 Validation → tampilkan pesan ke kasir.

---

**Simulasi Lengkap: Login, Buka Shift, Ganti/Tutup Shift**
Alur umum saat pergantian karyawan/shift.

1) Login user
   - POST `/pos/auth/login` (Body: `{ email, password }`) → 200 + token

2) Periksa shift aktif
   - GET `/pos/shift/aktif`
   - Jika shift milik user lain, tampilkan opsi `Request Handover` atau `Buka Shift Baru` (tergantung kebijakan).

3) Buka shift (kasir baru/mulai tugas)
   - POST `/pos/shift` dengan body:
     ```json
     {
       "cabang_id": 1,
       "user_id": 3,
       "saldo_awal": 300000,
       "notes": "Pagi",
       "client_request_id": "open-shift-20251122-3"
     }
     ```
   - Expect 201 + `shift.id`

4) Tutup/serah terima shift
   - POST `/pos/shift/{shift}/tutup`
   - Body contoh:
     ```json
     {
       "saldo_akhir": 450000,
       "catatan": "Serah terima ke kasir siang",
       "kasir_penutup_id": 4
     }
     ```
   - Expect 200, response menyertakan `laporan_shift`:
     - total penjualan
     - ringkasan per metode pembayaran
     - jumlah transaksi
     - perbedaan kas (jika ada)

Efek samping server pada penutupan:
- Status shift diubah menjadi `closed`.
- `laporan_shift` disimpan dan dapat diekspor.
- Jika ada selisih kas yang tidak dijelaskan, tandai untuk supervisor review.

---

**Ringkasan Endpoint yang Sering Digunakan pada POS Mobile (untuk transaksi & shift)**
- `POST /pos/auth/login`, `POST /pos/auth/logout`, `GET /pos/auth/me`
- `GET /pos/shift/aktif`, `POST /pos/shift`, `POST /pos/shift/{shift}/tutup`, `GET /pos/shift/{shift}`
- `GET /pos/produk`, `GET /pos/produk/{id}`
- `POST /pos/transaksi`, `GET /pos/transaksi/{id}`, `POST /pos/transaksi/{id}/batal`
- `POST /pos/kalibrasi`, `GET /pos/kalibrasi/last`
- `POST /pos/sinkronisasi` (batch upload dari client offline)

---

Checklist implementasi pada backend (expected behaviour):
- Transaksi harus idempotent bila diberikan `client_request_id`.
- Mutasi stok harus tercatat per transaksi dan bisa di-rollback saat pembatalan.
- Penutupan shift harus menghasilkan `laporan_shift` yang menjelaskan item keuangan dan transaksi.
- Sinkronisasi batch harus mengembalikan status per item (`ok` / `conflict` / `error`) dan ID server bila berhasil.

---

Jika Anda mau, saya bisa:
- menambahkan contoh `curl` / Postman collection untuk tiap simulasi,
- atau memetakan endpoint ke route names yang persis ada di proyek (mis. `pos.transaksi.store`).

File ini: `docs/pos-api-mobile.md`.

---

**Contoh `curl` (quick test)**

Catatan: ganti `BASE_URL` dan `TOKEN` sesuai environment.

1) Login (dapatkan token/session)

```bash
BASE_URL="http://localhost:8000/api"
curl -s -X POST "$BASE_URL/pos/auth/login" \
  -H "Content-Type: application/json" \
  -d '{ "email": "kasir@cabang.local", "password": "secret" }'
```

Contoh response sukses mengandung token atau session cookie.

2) Cek shift aktif

```bash
curl -s -X GET "$BASE_URL/pos/shift/aktif" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

3) Buka shift

```bash
curl -s -X POST "$BASE_URL/pos/shift" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{ "cabang_id": 1, "user_id": 3, "saldo_awal": 500000, "client_request_id": "shift-open-20251122-3" }'
```

4) Buat transaksi (contoh pembayaran tunai)

```bash
curl -s -X POST "$BASE_URL/pos/transaksi" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "client_request_id": "txn-20251122-0001",
    "shift_id": 11,
    "user_id": 3,
    "items": [
      { "produk_id": 101, "qty": 1, "harga": 18000, "satuan_id": 1, "kalibrasi_id": 55 },
      { "produk_id": 202, "qty": 2, "harga": 15000, "satuan_id": 1 }
    ],
    "pembayaran": [ { "metode": "tunai", "jumlah": 48000 } ],
    "catatan": "No sugar"
  }'
```

5) Ambil detail transaksi (untuk struk)

```bash
curl -s -X GET "$BASE_URL/pos/transaksi/12345" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

6) Tutup shift

```bash
curl -s -X POST "$BASE_URL/pos/shift/11/tutup" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{ "saldo_akhir": 450000, "catatan": "Serah terima ke kasir siang", "kasir_penutup_id": 4 }'
```

7) Sinkronisasi batch (offline → online)

```bash
curl -s -X POST "$BASE_URL/pos/sinkronisasi" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{ "id_perangkat": "dev-001", "items": [ { "type": "transaksi", "client_request_id": "txn-20251122-0001", "payload": { /* transaksi */ } } ] }'
```

---

**TO-DO untuk Aplikasi Mobile (Flutter)**

Tujuan: daftar pekerjaan fitur dan wireframe yang diperlukan agar aplikasi mobile POS bisa menangani transaksi minuman, manajemen shift, pencatatan kalibrasi, dan sinkronisasi offline.

- **Arsitektur & Integrasi (harus dibuat)**
  - Setup autentikasi (Sanctum / Bearer token) dan penyimpanan token aman (secure storage).
  - Modul offline queue: penyimpanan transaksi lokal (SQLite / Hive) + sinkronisasi background.
  - Mekanisme `client_request_id` generator (UUID + timestamp) untuk idempotency.
  - Handler retry / conflict resolution untuk sinkronisasi.
  - Integrasi printing/struk (Bluetooth / ESC/POS) — optional.

- **Wireframes & mapping API (setiap wireframe: elemen UI & API utama yang dipanggil)**

  1) **Login Screen**
     - Elemen UI: email, password, tombol `Login`, feedback error, link `Forgot`.
     - API: `POST /pos/auth/login` → simpan token / session.

  2) **Dashboard / Shift Status**
     - Elemen UI: status shift (Open / Closed), `Buka Shift` button, `Tutup Shift` button (jika owner), ringkasan singkat (total hari ini), quick actions (Buat Order, Sinkronisasi).
     - API: `GET /pos/shift/aktif`, `POST /pos/shift`, `POST /pos/shift/{shift}/tutup`, `GET /pos/shift/{shift}` untuk detail.

  3) **Produk / Catalog Search**
     - Elemen UI: search bar, kategori filter, list produk (gambar, nama, harga, stok), tombol `Tambah` ke cart, badge stok rendah.
     - API: `GET /pos/produk?q=...`, `GET /pos/produk/{id}`. Jika perlu cek `GET /pos/stok/cabang/{cabang}` untuk jumlah tersedia.

  4) **Order / Cart Screen (Buat Pesanan)**
     - Elemen UI: daftar item (qty, harga), pilihan modifikasi (size, sugar), dropdown kalibrasi jika product.perlu_kalibrasi, total, pilihan metode pembayaran, tombol `Bayar`.
     - API: gunakan `POST /pos/transaksi` untuk submit. Ambil `GET /pos/kalibrasi/last` bila user perlu memilih kalibrasi.

  5) **Payment & Receipt Screen**
     - Elemen UI: ringkasan pembayaran, input jumlah diterima (tunai), konfirmasi pembayaran, tampilkan invoice/QR/nomor struk, tombol `Print`.
     - API: `POST /pos/transaksi` (pembayaran sudah submit), `GET /pos/transaksi/{id}` untuk detail struk.

  6) **Shift Close / Handover Screen**
     - Elemen UI: ringkasan laporan shift (total penjualan, transaksi per metode, kas awal, kas akhir input), field `saldo_akhir`, tombol `Tutup Shift`, tampilkan perbedaan kas dan alert jika ada selisih.
     - API: `GET /api/pos/laporan/shift/{shift}/ringkasan` (atau `GET /pos/shift/{shift}`), `POST /pos/shift/{shift}/tutup`.

  7) **Offline Queue & Sync Screen**
     - Elemen UI: daftar antrian (pending/failed/success), tombol `Sync Now`, history sinkronisasi, detail error pada item gagal.
     - API: `POST /pos/sinkronisasi` (batch), `GET /viewer/sinkronisasi/status` untuk status.

  8) **Stock & Alerts Screen**
     - Elemen UI: daftar stok cabang, filter stok rendah, tombol `Refresh`, detail mutasi stok per produk.
     - API: `GET /pos/stok/cabang/{cabang}`, `GET /pos/pos/stok/cabang/{cabang}/rendah` (atau `GET /pos/stok/low`), `GET /pos/stok/mutasi/{stokEtalase}`.

  9) **Settings / Profile**
     - Elemen UI: profil user, opsi logout, pilihan printing, opsi sinkronisasi, info device id.
     - API: `GET /pos/auth/me`, `POST /pos/auth/logout`.

- **Task checklist (implementasi Flutter)**
  - [ ] Setup project skeleton (modules: auth, products, orders, shift, sync, settings).
  - [ ] Implement secure token storage + auto-refresh/login fallback.
  - [ ] Implement product list + search with pagination.
  - [ ] Implement cart & order flow with `client_request_id` generation.
  - [ ] Implement kalibrasi picker for products yang perlu_kalibrasi (mengambil `GET /pos/kalibrasi/last`).
  - [ ] Implement offline queue (store transactions locally, background sync worker).
  - [ ] Implement shift open/close flows with laporan view.
  - [ ] Implement error handling & conflict resolution UI for sync failures.
  - [ ] Add unit tests for API client & E2E test flows for order -> sync -> shift close.
  - [ ] (Optional) Integrate print/struk via Bluetooth.

---
