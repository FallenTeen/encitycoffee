# 🔍 Quick Debug Reference - ProdukController

## Lihat Logs Real-Time

```bash
# Linux/Mac
tail -f storage/logs/laravel.log | grep PRODUK

# Windows PowerShell
Get-Content storage/logs/laravel.log -Wait | findstr PRODUK
```

## Methods & Debug Logs

| Method | Log Header | Cek Ini | Masalah |
|--------|-----------|---------|---------|
| **INDEX** | `=== PRODUK INDEX DEBUG ===` | `assigned_cabang_ids`, `total_produk` | Produk tidak muncul |
| **SHOW** | `=== PRODUK SHOW DEBUG ===` | `found`, `produk_name` | Produk tidak ditemukan |
| **CREATE** | `=== PRODUK CREATE DEBUG ===` | `kategori_count`, `satuan_count` | Kategori kosong |
| **STORE** | `=== PRODUK STORE DEBUG ===` | Validation errors, `stok_etalase_created` | Form tidak simpan |
| **EDIT** | `=== PRODUK EDIT DEBUG ===` | `Cabang Access Check`, `kategori_count` | Form tidak buka |
| **UPDATE** | `=== PRODUK UPDATE DEBUG ===` | Validation errors, `affected_cabang_ids` | Data tidak terupdate |
| **DESTROY** | `=== PRODUK DESTROY DEBUG ===` | `Cabang Access Check`, `affected_cabang_ids` | Tidak bisa delete |

## Critical Logs

```
⚠️  WARNINGS (Untuk troubleshoot)
- "User has no assigned cabang" → User belum dipasangkan ke cabang
- "Unauthorized cabang access" → Manager tidak punya akses ke cabang ini
- "Validation failed" → Field ada yg salah, lihat errors detail
- "Unauthorized access attempt" → User tidak punya role untuk action ini

✅ SUCCESS (Semua berjalan lancar)
- "Authorization passed" → User authorized untuk action
- "Produk Loaded" → Produk berhasil diambil
- "Options loaded" → Kategori/Satuan tersedia
- "Success" → Action berhasil (CREATE/UPDATE/DELETE)
```

## Debug Checklist

### ❌ Produk Tidak Muncul
- [ ] Buka log INDEX
- [ ] Cek `assigned_cabang_ids` - apakah kosong?
- [ ] Cek `total_produk` - berapa jumlahnya?
- [ ] Apakah ada stok untuk cabang ini?

### ❌ Kategori Tidak Muncul di Form
- [ ] Buka log CREATE/EDIT
- [ ] Cek `kategori_count` - apakah 0?
- [ ] SQL: `SELECT COUNT(*) FROM kategori_produk;`
- [ ] Jika 0, buat kategori terlebih dahulu

### ❌ Tidak Bisa Tambah/Edit (Manager)
- [ ] Buka log CREATE/STORE/EDIT/UPDATE
- [ ] Cek `Cabang Access Check` log
- [ ] Lihat apakah `user_assigned_cabang` kosong
- [ ] SQL: `SELECT * FROM user_cabang WHERE user_id = X;`

### ❌ Validation Error
- [ ] Lihat log STORE/UPDATE
- [ ] Buka `Validation failed` entry
- [ ] Lihat field yang error dan pesan error-nya
- [ ] Perbaiki input sesuai rule

## Database Checks (SQL)

```sql
-- User punya cabang?
SELECT u.id, u.name, u.role, c.id, c.nama 
FROM users u 
LEFT JOIN user_cabang uc ON u.id = uc.user_id 
LEFT JOIN cabang c ON uc.cabang_id = c.id 
WHERE u.id = 2;

-- Ada kategori?
SELECT COUNT(*) FROM kategori_produk;

-- Ada produk dengan stok?
SELECT p.id, p.nama, s.jumlah, b.nama 
FROM produk p 
LEFT JOIN stok_etalase s ON p.id = s.produk_id 
LEFT JOIN cabang b ON s.cabang_id = b.id 
WHERE s.jumlah > 0 AND b.id = 1;

-- Check satuan produk
SELECT DISTINCT nama_satuan FROM satuan_produk;
```

## Role Access Matrix

```
Role            | INDEX | SHOW | CREATE | STORE | EDIT | UPDATE | DESTROY
it_support      |  ✓    |  ✓   |   ✓    |  ✓   |  ✓   |  ✓    |   ✓
manager         |  ✓    |  ✓   |   ✓    |  ✓   |  ✓   |  ✓    |   ✓
supervisor      |  ✓    |  ✓   |   ✓    |  ✓   |  ✓   |  ✓    |   ✓
kasir           |  ✓    |  ✓   |   ✗    |  ✗   |  ✗   |  ✗    |   ✗

Catatan: manager/supervisor hanya bisa akses produk di cabang yang ditugaskan
```

## Jangan Lupa

✅ Setelah membuat kategori → Clear cache  
✅ Setelah update produk → Check affected cabang  
✅ Manager tanpa cabang → assign di admin panel  
✅ Produk harus punya stok untuk muncul di index  

---

**Lihat detail lengkap:** `DEBUG_LOGS_GUIDE.md`
