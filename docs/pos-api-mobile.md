**Dokumentasi API POS Mobile — Akurat & Komprehensif**

Dokumen ini merangkum seluruh endpoint POS Mobile di bawah prefix ` /api/pos/... ` beserta autentikasi, payload, response, dan alur penggunaan yang direkomendasikan untuk aplikasi mobile (kasir). Seluruh spesifikasi telah diselaraskan dengan routes dan controller aktual pada kode.

**Ikhtisar**
- Base URL: `https://<server>/api` atau `http://localhost:8000/api` (development).
- Autentikasi: `POST /pos/auth/login` menghasilkan token Sanctum; semua endpoint di bawah `/pos` (kecuali login) memerlukan header `Authorization: Bearer <token>`.
- Header umum: `Content-Type: application/json`, `Accept: application/json`.
- Peran & akses: `kasir`, `supervisor`, `manager`, `it_support` dengan pembatasan akses. Kasir hanya boleh mengakses shift miliknya sendiri.
- Pagination: endpoint daftar mengembalikan objek paginator Laravel (memiliki `data`, `links`, `meta`).
- Error umum:
  - 401/403: `{"error": "Tidak memiliki akses"}`.
  - 404: `{"error": "Tidak ada shift aktif"}` atau resource tidak ditemukan.
  - 422: `{"error": {...}}` atau `{"errors": {...}}` untuk validasi.
  - 400/500: `{"error": "Pesan error"}` untuk kondisi bisnis/gagal server.

---

**Autentikasi**
- `POST /pos/auth/login`
  - Body: `{ "email": "string", "password": "string" }`
  - Response (JSON, 200): `{ "user": { ... }, "token": "<sanctum_token>" }`
  - Validation error (422): `{ "errors": { "email": ["Kredensial salah"] } }`

- `POST /pos/auth/logout` (auth)
  - Response (200): `{ "message": "Berhasil logout" }`

- `GET /pos/auth/me` (auth)
  - Response (200): `{ "user": { ... } }`

---

**Shift**
- `GET /pos/shift/aktif` (auth)
  - Mengambil shift yang sedang `buka` milik user login.
  - Response (200): `{ "shift": { id, user, cabang, kalibrasi: [...], ... } }`
  - Response (404): `{ "error": "Tidak ada shift aktif" }`

- `POST /pos/shift/buka` (auth)
  - Body: `{ "cabang_id": number, "saldo_awal": number }`
  - Response (201): `{ "shift": { id, cabang, waktu_buka, status: "buka" } }`
  - Error (400): user sudah punya shift yang masih buka.

- `POST /pos/shift/{shift}/tutup` (auth; owner/supervisor)
  - Body: `{ "saldo_akhir": number, "catatan": "string|null" }`
  - Response (200): `{ "shift": { id, saldo_akhir, saldo_diharapkan, selisih, total_tunai, total_qris, waktu_tutup, status: "tutup" } }`

- `GET /pos/shift/{shift}` (auth)
  - Detail lengkap: user, cabang, transaksi (item & pembayaran), kalibrasi, mutasi stok.
  - Response (200): `{ "shift": { ... } }`

- `GET /pos/shift` (auth)
  - Query opsional: `status`, `tanggal`, `per_page`.
  - Return: paginator (dibatasi sesuai peran; kasir hanya shift miliknya).

---

**Kalibrasi**
- `POST /pos/kalibrasi` (auth; owner shift)
  - Body: `{ "shift_id": number, "produk_id": number, "nomor_percobaan": number, "berat_beans_gram": number, "terpilih": boolean?, "catatan": string? }`
  - Efek: mengurangi stok `produksi_minuman` sesuai berat (kg), mencatat `MutasiStok` tipe `kalibrasi`.
  - Response (201/200): `{ "kalibrasi": { id, shift_id, produk_id, nomor_percobaan, berat_beans_gram, terpilih, catatan, produk: {...} } }`

- `PUT /pos/kalibrasi/{kalibrasi}/pilih` (auth; owner/supervisor)
  - Menandai kalibrasi terpilih untuk produk di shift tersebut (mendiset lainnya ke `false`).
  - Response (200): `{ "kalibrasi": { ... } }`

- `GET /pos/kalibrasi/shift/{shift}` (auth)
  - Response (200): `{ "kalibrasi": [ { ... }, ... ] }`

---

**Produk**
- `GET /pos/produk` (auth)
  - Query: `kategori_id?`, `tipe?` (`beans|minuman|snack`), `search?`.
  - Response (200): paginator berisi produk aktif (`data`, `links`, `meta`).

- `GET /pos/produk/{produk}` (auth)
  - Response (200): objek produk dengan relasi `kategori`, `satuan`.

- `GET /pos/produk/tipe/{tipe}` (auth)
  - Response (200): paginator produk berdasarkan tipe.

- `GET /pos/produk/{produk}/satuan` (auth)
  - Response (200): daftar satuan produk (array).

---

**Stok**
- `GET /pos/stok/cabang/{cabang}` (auth)
  - Response: `{ "cabang": {id,nama}, "stok_produksi_minuman": [...], "stok_penjualan_retail": [...], "statistik": { total_item, stok_rendah, nilai_inventori } }`

- `GET /pos/stok/cabang/{cabang}/rendah` (auth)
  - Response: `{ "stok_rendah": [...], "kelompok": { kritis|tinggi|sedang }, "statistik": { ... }, "rekomendasi_pembelian_total": number }`

- `GET /pos/stok/cabang/{cabang}/mendekati-kadaluarsa` (auth)
  - Query: `hari?` (default 30).
  - Response: `{ "data": [ batch dengan produk & cabang ] }`

- `GET /pos/stok/mutasi/{stokEtalase}` (auth)
  - Query: `tipe[]?` (`masuk|keluar|penyesuaian|kalibrasi|tidak_teralokasi`), `tanggal_mulai?`, `tanggal_selesai?`, `user_id?`, `per_page?`.
  - Response: paginator mutasi.

---

**Transaksi**
- `POST /pos/transaksi` (auth; owner shift)
  - Body:
    ```json
    {
      "shift_id": 11,
      "items": [
        { "produk_id": 101, "jumlah": 1, "catatan": "string?" }
      ],
      "pembayaran": [
        { "metode": "tunai|qris", "jumlah": 48000, "referensi": "string?" }
      ],
      "diskon": 0,
      "pajak": 0,
      "catatan": "string?"
    }
    ```
  - Response (201): objek transaksi (dengan item, pembayaran, shift, cabang).
  - Error (400): `{"error": "Shift tidak terbuka"}`; (403) bila bukan owner shift.

- `GET /pos/transaksi/{transaksi}` (auth)
  - Response (200): transaksi + relasi `item.produk`, `pembayaran`, `shift`, `cabang`.

- `PUT /pos/transaksi/{transaksi}/batal` (auth)
  - Body: `{ "alasan": "string|min:10" }`
  - Response (200): `{ "message": "Transaksi berhasil dibatalkan", "transaksi": { ... } }`

- `GET /pos/transaksi/shift/{shift}` (auth)
  - Query: `status?`, `per_page?` (default 15).
  - Response: paginator transaksi per shift (akses kasir dibatasi ke shiftnya).

---

**Laporan**
- `GET /pos/laporan/shift/{shift}/ringkasan` (auth)
  - Response: `{ shift_id, total_transaksi, total_pendapatan_tunai, total_pendapatan_qris }`

- `GET /pos/laporan/cabang/{cabang}/harian` (auth)
  - Response: ringkasan harian per cabang (tanggal, total terjual, pendapatan, jumlah transaksi). Parameter umum: `tanggal_mulai`, `tanggal_akhir`.

- `GET /pos/laporan/cabang/{cabang}/penjualan-produk` (auth)
  - Response: `{ filters, ringkasanKategori, ringkasanTipe, trendHarian }`.

- `GET /pos/laporan/cabang/{cabang}/stok` (auth)
  - Response: ringkasan stok/inventori per cabang (nilai inventori, filter kategori/status).

- `GET /pos/laporan/user/{user}/kinerja` (auth)
  - Response: agregasi kinerja kasir (total shift, total transaksi, total penjualan, menit kerja, selisih total). Query: `cabang_id?`, `tanggal_mulai?`, `tanggal_akhir?`.

---

**Sinkronisasi**
- `POST /pos/sinkronisasi/antrian` (auth)
  - Body: `{ "id_perangkat": "string", "tipe_entitas": "transaksi|mutasi_stok|shift", "id_entitas": any, "payload": object|string }`
  - Response (200): `{ "antrian": { id, id_perangkat, tipe_entitas, id_entitas, status: "pending", jumlah_percobaan: 0, ... } }`

- `POST /pos/sinkronisasi/proses` (auth)
  - Body/Query: `id_perangkat` (wajib).
  - Response (200): `{ "jumlah_berhasil": number, "jumlah_gagal": number }`.
  - Catatan: proses saat ini melakukan create/update naive sesuai `payload`; deduplikasi belum tersedia di server.

- `GET /pos/sinkronisasi/status` (auth)
  - Query: `id_perangkat`.
  - Response (200): `{ id_perangkat, statistik: { pending, tersinkronisasi, gagal }, item_pending: [...], terakhir_sinkronisasi: datetime|null }`.

---

**Viewer (Read-only JSON, tanpa auth)**
Endpoint di bawah prefix `/api/viewer/...` menyediakan data publik read-only seperti daftar shift, produk, stok, mutasi, dan status sinkronisasi. Cocok untuk dashboard ringan atau mode demo tanpa login.

---

**Alur Penggunaan yang Direkomendasikan**

- Membeli minuman (kasir)
  1. Login → dapatkan token.
  2. `GET /pos/shift/aktif`; jika 404, buka shift via `POST /pos/shift/buka`.
  3. Cari/pilih produk `GET /pos/produk` (filter `search`, `kategori_id`, `tipe`).
  4. Jika perlu kalibrasi, catat via `POST /pos/kalibrasi` atau pilih via `PUT /pos/kalibrasi/{kalibrasi}/pilih`.
  5. Buat transaksi via `POST /pos/transaksi` (gunakan `jumlah` bukan `qty`).
  6. Ambil detail struk via `GET /pos/transaksi/{id}`; cetak.

- Login, buka & tutup shift
  - Buka: `POST /pos/shift/buka` dengan `cabang_id`, `saldo_awal`.
  - Tutup: `POST /pos/shift/{shift}/tutup` dengan `saldo_akhir`, `catatan`.

- Offline sync
  - Tambah antrian: `POST /pos/sinkronisasi/antrian` per item lokal.
  - Proses batch: `POST /pos/sinkronisasi/proses?id_perangkat=...`.
  - Pantau: `GET /pos/sinkronisasi/status?id_perangkat=...`.
  - Catatan: tambahkan `client_request_id` di dalam `payload` sisi klien untuk deduplikasi; server belum melakukan deduplikasi otomatis.

---

**Contoh `curl` (diselaraskan dengan routes aktual)**

Catatan: ganti `BASE_URL` dan `TOKEN` sesuai environment.

1) Login (ambil token)
```bash
BASE_URL="http://localhost:8000/api"
curl -s -X POST "$BASE_URL/pos/auth/login" \
  -H "Content-Type: application/json" \
  -d '{ "email": "kasir@cabang.local", "password": "secret" }'
```

2) Cek shift aktif
```bash
curl -s -X GET "$BASE_URL/pos/shift/aktif" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

3) Buka shift
```bash
curl -s -X POST "$BASE_URL/pos/shift/buka" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{ "cabang_id": 1, "saldo_awal": 500000 }'
```

4) Buat transaksi (pembayaran tunai)
```bash
curl -s -X POST "$BASE_URL/pos/transaksi" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "shift_id": 11,
    "items": [ { "produk_id": 101, "jumlah": 1 } ],
    "pembayaran": [ { "metode": "tunai", "jumlah": 18000 } ]
  }'
```

5) Detail transaksi (untuk struk)
```bash
curl -s -X GET "$BASE_URL/pos/transaksi/12345" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

6) Batalkan transaksi
```bash
curl -s -X PUT "$BASE_URL/pos/transaksi/12345/batal" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{ "alasan": "Salah input harga item" }'
```

7) Tutup shift
```bash
curl -s -X POST "$BASE_URL/pos/shift/11/tutup" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{ "saldo_akhir": 450000, "catatan": "Serah terima ke kasir siang" }'
```

8) Sinkronisasi batch
```bash
curl -s -X POST "$BASE_URL/pos/sinkronisasi/antrian" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{ "id_perangkat": "dev-001", "tipe_entitas": "transaksi", "id_entitas": "local-123", "payload": { "nomor_invoice": "POS-..." } }'

curl -s -X POST "$BASE_URL/pos/sinkronisasi/proses" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{ "id_perangkat": "dev-001" }'

curl -s -X GET "$BASE_URL/pos/sinkronisasi/status?id_perangkat=dev-001" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

---

**Catatan Implementasi (Mobile)**
- Simpan token secara aman (secure storage) dan lakukan logout bila 401.
- Generasi `client_request_id` di sisi klien untuk setiap transaksi offline; kirimkan di dalam `payload` sinkronisasi.
- Tangani 403 (akses) dengan menampilkan informasi bahwa kasir hanya bisa mengakses shiftnya sendiri.
- Gunakan pagination (`per_page`) untuk daftar panjang (produk, mutasi, transaksi).
