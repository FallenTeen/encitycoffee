# Dokumentasi API POS Untuk Aplikasi Mobile (Indonesian)

Dokumentasi ini menjelaskan endpoint yang tersedia untuk aplikasi mobile POS. Endpoint terbagi menjadi dua grup:
- `/api/pos/...` — endpoint utama yang memerlukan autentikasi (session atau token).
- `/api/viewer/...` — endpoint read-only untuk viewer yang hanya mengekspor data JSON.

**Catatan autentikasi**: Di environment pengujian kami menggunakan session guard (`actingAs`). Untuk produksi, Anda bisa menggunakan `sanctum` token. Pastikan header `Authorization: Bearer <token>` dikirim bila menggunakan token.

---

**Format umum**
- Semua request/response menggunakan JSON.
- Pastikan header `Content-Type: application/json` pada request body.

---

## Autentikasi

### POST /api/pos/auth/login
- Deskripsi: Login user POS.
- Body:
  - `email` (string, required)
  - `password` (string, required)
- Response 200:
  {
    "token": "<token>",
    "user": { ... }
  }
- Response 422/400: validasi gagal atau kredensial salah.

### POST /api/pos/auth/logout
- Deskripsi: Logout (harus autentikasi).
- Header: `Authorization: Bearer <token>` (jika menggunakan token) atau session cookie.
- Response 200: `{ "message": "logged out" }`

### GET /api/pos/auth/me
- Deskripsi: Ambil info user saat ini.
- Response 200: `{ "user": { ... } }`

---

## Shift

### GET /api/pos/shift/aktif
- Deskripsi: Ambil shift aktif untuk user yang sedang login.
- Response 200: `{ "shift": { ... } }` atau 404 jika tidak ada.

### POST /api/pos/shift/buka
- Deskripsi: Buka shift baru.
- Body:
  - `cabang_id` (integer, required)
  - `saldo_awal` (number, required)
- Response 200: `{ "shift": { id, user_id, cabang_id, saldo_awal, waktu_buka, status } }`

### POST /api/pos/shift/{shift}/tutup
- Deskripsi: Tutup shift. Hanya pemilik shift atau supervisor.
- Body:
  - `saldo_akhir` (number, required)
  - `catatan` (string, optional)
- Response 200: `{ "shift": { ... } }` (data shift lengkap)
- Response 403: tidak memiliki akses

### GET /api/pos/shift/{shift}
- Deskripsi: Tampilkan detail shift (transaksi, kalibrasi, mutasi stok).

### GET /api/viewer/shift
- Deskripsi: Daftar shift (read-only viewer), mendukung query params `tanggal`, `status`, `per_page`.

---

## Kalibrasi

### POST /api/pos/kalibrasi
- Deskripsi: Simpan data kalibrasi (mis. berat beans per cup).
- Body:
  - `shift_id` (integer, required)
  - `produk_id` (integer, required)
  - `nomor_percobaan` (integer)
  - `berat_beans_gram` (number, required)
  - `terpilih` (boolean)
- Response 200: `{ "kalibrasi": { ... } }`

### PUT /api/pos/kalibrasi/{kalibrasi}/pilih
- Deskripsi: Tandai kalibrasi terpilih.

### GET /api/pos/kalibrasi/shift/{shift}
- Deskripsi: Ambil semua kalibrasi untuk shift tertentu.

---

## Produk

### GET /api/pos/produk
- Deskripsi: Daftar produk.
- Query params: `q`, `kategori`, `tipe`, `per_page`.

### GET /api/pos/produk/{produk}
- Deskripsi: Detail produk.

### GET /api/pos/produk/tipe/{tipe}
- Deskripsi: Produk berdasarkan tipe (mis: minuman, beans, snack).

### GET /api/pos/produk/{produk}/satuan
- Deskripsi: Satuan produk.

---

## Stok

### GET /api/pos/stok/cabang/{cabang}
- Deskripsi: Ambil stok di cabang tertentu.

### GET /api/pos/stok/cabang/{cabang}/rendah
- Deskripsi: Ambil daftar stok yang berada di bawah ambang minimum.

### GET /api/pos/stok/cabang/{cabang}/mendekati-kadaluarsa?hari=15
- Deskripsi: Ambil batch yang mendekati tanggal kadaluarsa dalam X hari.
- Response 200: `{ "batches": [ { "nomor_batch": ..., "tanggal_kadaluarsa": ..., "jumlah": ... }, ... ] }

### GET /api/pos/stok/mutasi/{stokEtalase}
- Deskripsi: Riwayat mutasi untuk stok etalase tertentu.

---

## Transaksi

### POST /api/pos/transaksi
- Deskripsi: Buat transaksi penjualan.
- Body:
  - `shift_id` (integer, required)
  - `items` (array of { `produk_id`, `jumlah` })
  - `pembayaran` (array of { `metode`: 'tunai'|'qris'|'other', `jumlah` })
  - `catatan` (string, optional)
- Response 200: `{ "transaksi": { "id", "nomor_invoice", "total", "status": "selesai" } }`

Behavior penting:
- Untuk produk `minuman` yang `perlu_kalibrasi`, stok dikurangi berdasarkan kalibrasi terpilih (gram -> konversi ke kg bila stok tersimpan dalam kg).
- Untuk produk retail (beans, snack), stok dikurangi dari `penjualan_retail` etalase.

### PUT /api/pos/transaksi/{transaksi}/batal
- Deskripsi: Batalkan transaksi (mengembalikan stok dan membuat catatan mutasi).

### GET /api/pos/transaksi/{transaksi}
- Deskripsi: Detail transaksi termasuk item dan pembayaran.

### GET /api/viewer/transaksi/shift/{shift}
- Deskripsi: Daftar transaksi per shift (read-only viewer).

---

## Laporan

### GET /api/pos/laporan/shift/{shift}/ringkasan
- Deskripsi: Ringkasan keuangan dan transaksi untuk shift.
- Response: `keuangan`, `jumlah_transaksi`, `produk_terlaris`, dll.

### GET /api/pos/laporan/cabang/{cabang}/harian?tanggal=YYYY-MM-DD
- Deskripsi: Laporan harian cabang.

### GET /api/pos/laporan/cabang/{cabang}/penjualan-produk
- Deskripsi: Export penjualan produk untuk analitik.

### GET /api/viewer/laporan/cabang/{cabang}/stok
- Deskripsi: Laporan stok read-only.

---

## Sinkronisasi

### POST /api/pos/sinkronisasi/antrian
- Deskripsi: Tambah item ke antrian sinkronisasi perangkat.
- Body: `id_perangkat`, `tipe_entitas`, `payload` (json)

### POST /api/pos/sinkronisasi/proses
- Deskripsi: Proses antrian (admin atau worker internal).

### GET /api/viewer/sinkronisasi/status
- Deskripsi: Lihat status antrian (read-only).

---

## Contoh header (token)
- Authorization: Bearer <token>
- Content-Type: application/json

---
